<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryStock extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_id', 'quantity', 'unit_id', 'unit_cost', 'description'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} inventory stock")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'item_id',
        'quantity',
        'unit_id',
        'unit_cost',
        'description',
        'expiration_date',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_cost'       => 'decimal:2',
        'expiration_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitType::class, 'unit_id');
    }
}
