<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItemProperty extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'property_code',
        'model',
        'serial_no',
        'acquisition_date',
        'acquisition_cost',
        'depreciation_rate',
        'status',
        'remarks',
    ];

    protected $casts = [
        'acquisition_date'   => 'date',
        'acquisition_cost'   => 'decimal:2',
        'depreciation_rate'  => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['inventory_item_id', 'property_code', 'model', 'serial_no', 'acquisition_date', 'acquisition_cost', 'depreciation_rate', 'status'])
            ->setDescriptionForEvent(fn (string $e) => "{$e} inventory property record")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($property) {
            if (empty($property->property_code)) {
                $property->property_code = self::generatePropertyCode();
            }
        });
    }

    public static function generatePropertyCode(): string
    {
        $year = date('Y');
        $prefix = "PROP-{$year}-";

        $last = self::withTrashed()
            ->where('property_code', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(property_code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('property_code');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
