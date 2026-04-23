<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementQuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'procurement_item_id',
        'delivery_term',
        'technical_proposal',
        'bid_price',
        'is_free',
        'is_no_offer',
        'is_not_applicable',
        'status_id',
        'is_rebid'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_no_offer' => 'boolean',
        'is_not_applicable' => 'boolean',
        'is_checked' => 'boolean',
        'is_rebid' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo('App\Models\ProcurementItem', 'procurement_item_id')->with('item_unit_type');
    }


    public function quotation()
    {
        return $this->belongsTo('App\Models\ProcurementQuotation', 'quotation_id');
    }

      public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id' , 'id');
    }
    

}
