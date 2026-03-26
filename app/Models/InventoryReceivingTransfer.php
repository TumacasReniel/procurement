<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceivingTransfer extends Model
{
    protected $fillable = [
        'po_id',
        'procurement_item_id',
        'inventory_id',
        'inventory_stock_id',
        'quantity',
        'transferred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'transferred_at' => 'datetime',
    ];
}
