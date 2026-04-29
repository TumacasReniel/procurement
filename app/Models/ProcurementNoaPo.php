<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProcurementNoaPo extends Model
{
    use LogsActivity;
       protected $fillable = [
        'code',
        'po_date',
        'delivery_term',
        'payment_term',
        'date_of_delivery',
        'actual_delivery_date',
        'place_of_delivery_id',
        'noa_id',
        'created_by_id',
        'approved_by_id',
        'updated_by_id',
        'procurement_id',
        'released_at',
        'conformed_at',
        'status_id',

    ];

    protected $casts = [
        'po_date' => 'date',
        'date_of_delivery' => 'date',
        'actual_delivery_date' => 'date',
        'released_at' => 'datetime',
        'conformed_at' => 'datetime',
    ];

    public function place_of_delivery()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'place_of_delivery_id' );
    }

    public function noa()
    {
        return $this->belongsTo('App\Models\procurementBacNoa', 'noa_id' );
    }

    public function iar()
    {
        return $this->hasOne('App\Models\procurementPoIar', 'po_id')->latestOfMany();
    }

    public function iars()
    {
        return $this->hasMany('App\Models\procurementPoIar', 'po_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function deliveries()
    {
        return $this->hasMany('App\Models\ProcurementPoDelivery', 'po_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
      
    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }

    public function ntp()
    {
        return $this->hasOne('App\Models\ProcurementPoNtp', 'po_id');
    }


    public function comments()
    {
        return $this->morphMany('App\Models\RequestComment', 'commentable');
    }

    
    public static function generatePONumber($date = null)
    {
        if ($date) {
            $year = date("y", strtotime($date));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime($date));
        } else {
            $year = date("y", strtotime("now"));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime("now"));
        }

        $count = self::whereYear('po_date', date("Y", strtotime($date ?? "now")))
                     ->whereMonth('po_date', $month)
                     ->count() + 1;

        return 'PO-' .$year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['code','po_date','delivery_term','payment_term','date_of_delivery','actual_delivery_date','place_of_delivery_id','noa_id','created_by_id','approved_by_id','updated_by_id','procurement_id','released_at','conformed_at','status_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "Purchase Order {$eventName}")
        ->useLogName('Purchase Order')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
