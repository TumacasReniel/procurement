<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProcurementNoaPo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryReceiving extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_id', 'inventory_stock_id', 'quantity', 'unit_cost', 'status_id', 'approved_by_id', 'received_at', 'remarks'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} inventory receiving")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'item_id',
        'inventory_stock_id',
        'quantity',
        'unit_cost',
        'po_id',
        'procurement_item_id',
        'approved_by_id',
        'status_id',
        'received_at',
        'remarks',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'unit_cost'   => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }

    public function po()
    {
        return $this->belongsTo(ProcurementNoaPo::class, 'po_id');
    }
}
