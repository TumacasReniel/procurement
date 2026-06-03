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
        'category_id',
    ];

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'item_id');
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
        return $this->hasMany(InventoryWithdrawal::class, 'inventory_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->code)) {
                $item->code = self::generateItemCode();
            }
        });
    }

    public static function generateItemCode()
    {
        $prefix = 'ITM-';

        $lastCode = self::where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $nextNumber = 1;

        if ($lastCode && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastCode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $code = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
