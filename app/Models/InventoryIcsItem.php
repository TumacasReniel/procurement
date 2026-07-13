<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryIcsItem extends Model
{
    protected $table = 'inventory_ics_items';

    protected $fillable = [
        'ics_id', 'item_id', 'unit_of_measure',
        'quantity', 'unit_value', 'total_value',
        'estimated_useful_life', 'description', 'remarks',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_value' => 'decimal:2',
        'total_value'=> 'decimal:2',
    ];

    public function ics()  { return $this->belongsTo(InventoryIcs::class, 'ics_id'); }
    public function item() { return $this->belongsTo(InventoryItem::class, 'item_id'); }
}
