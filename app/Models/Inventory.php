<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'unit_id',
        'min_stock_level',
    ];

    public function category()
    {
        return $this->belongsTo(ListDropdown::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(ListDropdown::class, 'unit_id');
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function totalQuantity()
    {
        return $this->stocks->sum('quantity');
    }

    public function isLowStock()
    {
        return $this->totalQuantity() <= $this->min_stock_level;
    }
}

