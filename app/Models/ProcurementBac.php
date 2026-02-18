<?php

namespace App\Models;

use App\Models\ListStatus;
use Illuminate\Database\Eloquent\Model;

class ProcurementBac extends Model
{
    protected $fillable = [
        'procurement_id',
        'code',
        'type',
        'body',
        'created_by_id',
        'approved_by_id',
        'status_id',
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

        // Define status hierarchy from highest to lowest
        $status_hierarchy = [
            'Delivered/For Inspection' => ['full' => 'Delivered/For Inspection', 'partial' => 'Partially Delivered/For Inspection'],
            'PO Delivered/For Inspection' => ['full' => 'PO Delivered/For Inspection', 'partial' => 'PO Partially Delivered/For Inspection'],
            'PO Conformed' => ['full' => 'PO Conformed', 'partial' => 'PO Partially Conformed'],
            'PO Issued' => ['full' => 'PO Issued', 'partial' => 'PO Partially Issued'],
            'PO Pending' => ['full' => 'PO Pending', 'partial' => 'Partially PO Pending'],
            'Conformed' => ['full' => 'Conformed', 'partial' => 'Partially NOA Conformed'],
            'Served to Supplier' => ['full' => 'Awarded', 'partial' => 'Partially Awarded'],
        ];

        //  // Handle special cases for Not Conformed and PO Not Conformed
        // if ($noas->contains(fn($noa) => $noa->status->name == 'Not Conformed')) {
        //     //$this->update_items_for_re_award($not_conformed_noa);
        //     return $this->determine_re_award_or_rebid();
        // }

        // if ($noas->contains(fn($noa) => $noa->status->name == 'PO Not Conformed')) {
        //     //$this->update_items_for_not_conformed();
        //     return $this->determine_re_award_or_rebid();
        // }


        foreach ($status_hierarchy as $noa_status => $procurement_statuses) {
            if ($noas->contains(fn($noa) => $noa->status->name === $noa_status)) {
                return $noas->every(fn($noa) => $noa->status->name === $noa_status)
                    ? ListStatus::getID($procurement_statuses['full'], 'Procurement')
                    : ListStatus::getID($procurement_statuses['partial'], 'Procurement');
            }
        }

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
                ->update(['status_id' => ListStatus::getID('Awarded','Procurement')]);
        } else {
            // Fallback to original logic if no NOA provided
            $available_quotation_items = $this->procurement->quotations
                ->flatMap->items
                ->filter(fn($item) => $item->status_id == ListStatus::getID('Available for Re-award','Procurement'));

            foreach ($available_quotation_items as $item) {
                $item->update(['status_id' => ListStatus::getID('Awarded','Procurement')]);
            }
        }
    }

    private function update_items_for_not_conformed()
    {
        $available_quotation_items = $this->procurement->quotations
            ->flatMap->items
            ->filter(fn($item) => $item->status_id == ListStatus::getID('Available for Re-award','Procurement') ||
                                  $item->status_id == ListStatus::getID('Awarded','Procurement'));

        foreach ($available_quotation_items as $item) {
          if ($item->status_id == ListStatus::getID('Awarded','Procurement')) {
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

     
        // Define status hierarchy from highest to lowest
        $status_hierarchy = [
            'Delivered/For Inspection' => 'Partially Delivered/For Inspection',
            'PO Delivered/For Inspection' => 'PO Partially Delivered/For Inspection',
            'PO Conformed' => 'PO Partially Conformed',
            'PO Issued' => 'PO Partially Issued',
            'PO Pending' => 'PO Pending',
            'Conformed' => 'Partially NOA Conformed',
            'Served to Supplier' => 'Partially Awarded',
        ];

    
        foreach ($status_hierarchy as $full_status => $partial_status) {
            if ($noas->contains(fn($noa) => $noa->status->name === $full_status)) {
                return $noas->every(fn($noa) => $noa->status->name === $full_status)
                    ? ListStatus::getID($full_status, 'Procurement')
                    : ListStatus::getID($partial_status, 'Procurement');
            }
        }

     

        // Handle special cases for Not Conformed and PO Not Conformed
        if ($noas->contains(fn($noa) => $noa->status->name == 'Not Conformed')) {
            //$this->update_items_for_re_award();
            return $this->determine_re_award_or_rebid();
        }

        if ($noas->contains(fn($noa) => $noa->status->name == 'PO Not Conformed')) {
            $this->update_items_for_not_conformed();
            return $this->determine_re_award_or_rebid();
        }

        return null;
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['procurement_id','code','type','body','created_by_id','approved_by_id','status_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "BAC Resolution {$eventName}")
        ->useLogName('BAC Resolution')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
