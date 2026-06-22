<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProcurementNoaPo;
use App\Models\InventoryItem;
use App\Models\InventoryStock;

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
        'quantity'       => 'decimal:2',
        'transferred_at' => 'datetime',
    ];

    public function po()
    {
        return $this->belongsTo(ProcurementNoaPo::class, 'po_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function inventoryStock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }
}
