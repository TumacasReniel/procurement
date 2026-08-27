<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryStockAdjustment extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'adjustment_no',
        'item_id',
        'stock_id',
        'type',
        'quantity_before',
        'quantity_adjusted',
        'quantity_after',
        'reason',
        'remarks',
        'adjusted_by_id',
        'approved_by_id',
        'status_id',
        'adjustment_date',
    ];

    protected $casts = [
        'quantity_before'    => 'decimal:2',
        'quantity_adjusted'  => 'decimal:2',
        'quantity_after'     => 'decimal:2',
        'adjustment_date'    => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['item_id', 'type', 'quantity_before', 'quantity_adjusted', 'quantity_after', 'reason', 'status_id'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} stock adjustment")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adj) {
            if (empty($adj->adjustment_no)) {
                $adj->adjustment_no = self::generateAdjustmentNo();
            }
        });
    }

    public static function generateAdjustmentNo(): string
    {
        $year   = date('Y');
        $prefix = "ADJ-{$year}-";

        $last = self::where('adjustment_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(adjustment_no, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('adjustment_no');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
