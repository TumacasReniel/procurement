<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'entry_date',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'stock_id');
    }
}
