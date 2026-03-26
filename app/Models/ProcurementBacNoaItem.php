<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementBacNoaItem extends Model
{
     protected $fillable = [
        'procurement_bac_noa_id',
        'item_id',
    ];

    public function noa()
    {
        return $this->belongsTo('App\Models\ProcurementBacNoa', 'procurement_bac_noa_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\ProcurementQuotationItem', 'item_id');
    }
}
