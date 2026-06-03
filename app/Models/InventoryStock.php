<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'quantity',
        'unit_id',
        'unit_cost',
        'description',
    ];

    protected $casts = [
        'quantity'  => 'decimal:2',
        'unit_cost' => 'decimal:2',
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
