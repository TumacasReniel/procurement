<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'inventory_id',
        'inventory_stock_id',
        'location_id',
        'requested_by_id',
        'item_name',
        'quantity',
        'released_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function location()
    {
        return $this->belongsTo(ListDropdown::class, 'location_id');
    }

    public function requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by_id')->with('profile');
    }

    public static function generateReferenceNumber($date = null): string
    {
        $timestamp = $date ? now()->parse($date) : now();
        $year = $timestamp->format('y');
        $month = $timestamp->format('m');

        $count = self::whereYear('created_at', $timestamp->year)
            ->whereMonth('created_at', $timestamp->month)
            ->count() + 1;

        return 'WDR-' . $year . '-' . $month . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
