<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'stock_id',
        'category_id',
        'quantity',
        'unit_cost',
        'expiration',
    ];

    protected $casts = [
        'expiration' => 'date',
    ];

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function category()
    {
        return $this->belongsTo(ListDropdown::class, 'category_id');
    }

    public function receivings()
    {
        return $this->hasMany(InventoryReceiving::class, 'item_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(InventoryWithdrawal::class, 'item_id');
    }
}
