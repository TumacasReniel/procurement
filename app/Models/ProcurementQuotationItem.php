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
        'status_id',
        'is_rebid'
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
