<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStockDrain extends Model
{
    protected $fillable = [
        'inventory_stock_id',
        'item_id',
        'source_type',
        'source_id',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'quantity'  => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
