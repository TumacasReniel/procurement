<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementQuotation extends Model
{
    use SoftDeletes;

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
        $timestamp = strtotime($date ?? "now");
        $year = date("y", $timestamp);
        $month = date("m", $timestamp);
        $prefix = 'RFQ-' . $year . '-' . $month . '-';

        $lastCode = self::where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $nextNumber = 1;

        if ($lastCode && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastCode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $code = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
