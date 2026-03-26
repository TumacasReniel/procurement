<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementItem extends Model
{
     protected $fillable = [
        'procurement_id',
        'item_unit_type_id',
        'item_description',
        'item_quantity',
        'item_unit_cost', 
        'total_cost',
        'status_id'
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function item_unit_type()
    {
        return $this->belongsTo('App\Models\UnitType', 'item_unit_type_id');
    }

    
    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }
}
