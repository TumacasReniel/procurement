<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItem extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'category_id'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} inventory item")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'code',
        'name',
        'reorder_level',
        'category_id',
    ];

    protected $casts = [
        'category_id' => 'integer',
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

    public function properties()
    {
        return $this->hasMany(InventoryItemProperty::class, 'inventory_item_id');
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
            ->orderByRaw('CAST(SUBSTRING(code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
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
