<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryPhysicalCountItem extends Model
{
    protected $table = 'inventory_physical_count_items';

    protected $fillable = [
        'count_id',
        'item_id',
        'system_quantity',
        'physical_quantity',
        'remarks',
    ];

    protected $casts = [
        'system_quantity'   => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'variance'          => 'decimal:2',
    ];

    public function count()
    {
        return $this->belongsTo(InventoryPhysicalCount::class, 'count_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
