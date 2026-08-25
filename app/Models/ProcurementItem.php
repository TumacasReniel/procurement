<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementItem extends Model
{
    use SoftDeletes;

     protected $fillable = [
        'item_no',
        'procurement_id',
        'ppmp_item_id',
        'item_unit_type_id',
        'item_name',
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

    public function ppmp_item()
    {
        return $this->belongsTo('App\Models\ProcurementPpmpItem', 'ppmp_item_id');
    }

    
    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }
}
