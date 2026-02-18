<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProcurementBacNoa extends Model
{
    use LogsActivity;
    protected $fillable = [
        'procurement_id',
        'procurement_bac_id',
        'procurement_quotation_id',
        'code',
        'created_by_id',
        'approved_by_id',
        'status_id',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function procurement_bac()
    {
        return $this->belongsTo('App\Models\ProcurementBac', 'procurement_bac_id');
    }

    public function procurement_quotation()
    {
        return $this->belongsTo('App\Models\ProcurementQuotation', 'procurement_quotation_id')->with('supplier.address' ,'supply_officer');
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

    public function items()
    {
        return $this->hasMany('App\Models\ProcurementBacNoaItem', 'procurement_bac_noa_id')->with('item.item.item_unit_type');
    }

    public function purchase_order()
    {
        return $this->hasOne('App\Models\ProcurementNoaPo', 'noa_id')->with('noa.items');
    }

    public function comments()
    {
        return $this->morphMany('App\Models\RequestComment', 'commentable');
    }

    public function activity()
    {
        return $this->morphMany('Spatie\Activitylog\Models\Activity', 'subject');
    }


    public static function generateNOANumber($date = null)
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
    
        return 'NOA-' .$year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['procurement_id','procurement_bac_id','procurement_quotation_id','code','created_by_id','approved_by_id','status_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "NOA {$eventName}")
        ->useLogName('NOA')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
