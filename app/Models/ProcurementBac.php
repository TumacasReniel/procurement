<?php

namespace App\Models;

use App\Models\ListStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class ProcurementBac extends Model
{
    protected $fillable = [
        'procurement_id',
        'code',
        'type',
        'body',
        'created_by_id',
        'approved_by_id',
        'approved_at',
        'status_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id')->with('quotations.items');
    }

    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'created_by_id')->with('profile');
    }

    
    public function approved_by()
    {
        return $this->belongsTo('App\Models\User', 'approved_by_id')->with('profile');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }

    public function comments()
    {
        return $this->morphMany('App\Models\RequestComment', 'commentable');
    }


    public static function generateBACResolutionNumber($date = null)
    {
        if ($date) {
            $year = date("y", strtotime($date));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime($date));
        } else {
            $year = date("y", strtotime("now"));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime("now"));
        }
    
        $count = self::whereYear('created_at', date("Y", strtotime($date ?? "now")))
                     ->whereMonth('created_at', $month)
                     ->count() + 1;
    
        return 'BAC-' .$year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

     public function notice_of_awards()
    {
        return $this->hasMany('App\Models\ProcurementBacNoa', 'procurement_bac_id')->with('status' , 'items' , 'procurement_quotation');
    }

    public function overall_status($current_status)
    {
    
      
        $noas = $this->notice_of_awards;

        // If there are no NOAs, return current status
        if ($noas->isEmpty()) {
            return $current_status;
        }

        // Define status hierarchy from highest to lowest
        $status_hierarchy = [
            'Items Delivered' => ['full' => 'Items Delivered', 'partial' => 'Items Partially Delivered'],
            'PO Items Delivered' => ['full' => 'PO Items Delivered', 'partial' => 'PO Items Partially Delivered'],
            'PO Conformed' => ['full' => 'PO Conformed', 'partial' => 'PO Partially Conformed'],
            'Conformed' => ['full' => 'NOA Conformed', 'partial' => 'NOA Partially Conformed'],
            'Served to Supplier' => ['full' => 'NOA Served to Supplier', 'partial' => 'NOA Partially Served to Supplier'],
            'PO Issued' => ['full' => 'PO Issued', 'partial' => 'PO Partially Issued'],
            'PO Created' => ['full' => 'PO Created', 'partial' => 'PO Partially Created'],
            'Created' => ['full' => 'PO Created', 'partial' => 'PO Partially Created'],
        ];

        foreach ($status_hierarchy as $noa_status => $procurement_statuses) {
            if ($noas->contains(fn($noa) => $noa->status->name === $noa_status)) {
                $status_id = $noas->every(fn($noa) => $noa->status->name === $noa_status)
                    ? ListStatus::getID($procurement_statuses['full'], 'Procurement')
                    : ListStatus::getID($procurement_statuses['partial'], 'Procurement');
                
                // Return the status ID only if it's valid, otherwise return current status
                return $status_id ?? $current_status;
            }
        }

        // Fallback: If no NOA matches the hierarchy but we have NOAs, 
        // return the lowest status (Pending or Created)
        if ($noas->contains(fn($noa) => $noa->status->name === 'Pending')) {
            $status_id = ListStatus::getID('NOA Pending', 'Procurement');
            return $status_id ?? $current_status;
        }
        
        if ($noas->contains(fn($noa) => $noa->status->name === 'Created')) {
            $status_id = ListStatus::getID('NOA Created', 'Procurement');
            return $status_id ?? $current_status;
        }

        // If still no match, return the current status to avoid null
        return $current_status;
    
    }

    private function update_items_for_re_award($noa = null)
    {
        if ($noa) {
            // Update only items related to the specific NOA
            $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $noa->id)->get();
            $item_ids = $noa_items->pluck('item.procurement_item_id')->unique();
            ProcurementQuotationItem::where('quotation_id', $noa->procurement_quotation_id)
                ->whereIn('procurement_item_id', $item_ids)
                ->where('status_id', ListStatus::getID('Available for Re-award','Procurement'))
                ->update(['status_id' => ListStatus::getID('NOA Served to Supplier','Procurement')]);
        } else {
            // Fallback to original logic if no NOA provided
            $available_quotation_items = $this->procurement->quotations
                ->flatMap->items
                ->filter(fn($item) => $item->status_id == ListStatus::getID('Available for Re-award','Procurement'));

            foreach ($available_quotation_items as $item) {
                $item->update(['status_id' => ListStatus::getID('NOA Served to Supplier','Procurement')]);
            }
        }
    }

    private function update_items_for_not_conformed()
    {
        $available_quotation_items = $this->procurement->quotations
            ->flatMap->items
            ->filter(fn($item) => $item->status_id == ListStatus::getID('Available for Re-award','Procurement') ||
                                  $item->status_id == ListStatus::getID('NOA Served to Supplier','Procurement'));

        foreach ($available_quotation_items as $item) {
          if ($item->status_id == ListStatus::getID('NOA Served to Supplier','Procurement')) {
                $item->update(['status_id' => ListStatus::getID('Not Conformed','Procurement')]);
            }
        }
    }

    public function determine_re_award_or_rebid()
    {
        $has_available_re_award_items = $this->procurement->quotations->flatMap->items
            ->contains(fn($item) => $item->status->id == ListStatus::getID('Available for Re-award','Procurement'));


        return $has_available_re_award_items
            ? ListStatus::getID('Re-award','Procurement')
            : ListStatus::getID('Rebid','Procurement');
    }



    public function overall_substatus($current_status)
    {
        $noas = $this->notice_of_awards;

        // If there are no NOAs, return current status
        if ($noas->isEmpty()) {
            return $current_status;
        }

        // Define status hierarchy from highest to lowest
        $status_hierarchy = [
            'Items Delivered' => 'Items Delivered',
            'PO Items Delivered' => 'PO Items Delivered',
            'PO Conformed' => 'PO Conformed',
            'Conformed' => 'NOA Conformed',
            'Served to Supplier' => 'NOA Served to Supplier',
            'PO Issued' => 'PO Issued',
            'PO Created' => 'PO Created',
            'Created' => 'PO Created',
        ];

        foreach ($status_hierarchy as $noa_status => $procurement_status) {
            if ($noas->contains(fn($noa) => $noa->status->name === $noa_status)) {
                $status_id = ListStatus::getID($procurement_status, 'Procurement');
                return $status_id ?? $current_status;
            }
        }

        // Handle special cases for Not Conformed and PO Not Conformed
        if ($noas->contains(fn($noa) => $noa->status->name == 'Not Conformed')) {
            $status_id = $this->determine_re_award_or_rebid();
            return $status_id ?? $current_status;
        }

        if ($noas->contains(fn($noa) => $noa->status->name == 'PO Not Conformed')) {
            $this->update_items_for_not_conformed();
            $status_id = $this->determine_re_award_or_rebid();
            return $status_id ?? $current_status;
        }

        // Fallback: If no NOA matches the hierarchy but we have NOAs, 
        // return the lowest status
        if ($noas->contains(fn($noa) => $noa->status->name === 'Pending')) {
            $status_id = ListStatus::getID('NOA Pending', 'Procurement');
            return $status_id ?? $current_status;
        }
        
        if ($noas->contains(fn($noa) => $noa->status->name === 'Created')) {
            $status_id = ListStatus::getID('NOA Created', 'Procurement');
            return $status_id ?? $current_status;
        }

        // If still no match, return the current status to avoid null
        return $current_status;
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['procurement_id','code','type','body','created_by_id','approved_by_id','approved_at','status_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "BAC Resolution {$eventName}")
        ->useLogName('BAC Resolution')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
