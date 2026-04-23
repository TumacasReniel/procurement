<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function generateCode(): string
    {
        return DB::transaction(function () {
            $prefix = 'STK-' . now()->format('mY') . '-';
            $latest = self::lockForUpdate()
                ->where('code', 'like', $prefix . '%')
                ->orderByDesc('id')
                ->first();

            $count = $latest
                ? (int) substr((string) $latest->code, -4) + 1
                : 1;

            return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'stock_id');
    }
}
