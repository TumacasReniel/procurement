<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryWithdrawal extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['inventory_id', 'quantity', 'issued_quantity', 'unit_cost', 'status_id', 'requested_by_id', 'approved_by_id', 'released_at', 'remarks'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} inventory withdrawal")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'inventory_id',
        'quantity',
        'issued_quantity',
        'unit_cost',
        'requested_by_id',
        'approved_by_id',
        'status_id',
        'released_at',
        'remarks',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'issued_quantity' => 'decimal:2',
        'unit_cost'       => 'decimal:4',
        'released_at'     => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }
}
