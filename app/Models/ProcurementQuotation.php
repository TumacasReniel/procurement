<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementQuotation extends Model
{
     protected $fillable = [
        'code',
        'submission_not_later_than',
        'supplier_id',
        'supply_officer_id',
        'procurement_id',
        'status_id',
        'delivery_term',
        'place_of_delivery_id',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'supplier_id');
    }

    public function supply_officer()
    {
        return $this->belongsTo('App\Models\User', 'supply_officer_id')->with('profile');
    }


    public function items()
    {
        return $this->hasMany('App\Models\ProcurementQuotationItem', 'quotation_id')->with('item' , 'status');
    }
    
    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id' , 'id');
    }


    public static function generateRFQNumber($date = null)
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

        return 'RFQ-' . $year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
