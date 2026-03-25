<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'location_id',
        'quantity',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function location()
    {
        return $this->belongsTo(ListDropdown::class, 'location_id');
    }
}

