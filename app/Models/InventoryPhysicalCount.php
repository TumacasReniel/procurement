<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryPhysicalCount extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'count_no',
        'count_date',
        'remarks',
        'counted_by_id',
        'verified_by_id',
        'approved_by_id',
        'status_id',
    ];

    protected $casts = [
        'count_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['count_no', 'count_date', 'status_id', 'counted_by_id', 'verified_by_id', 'approved_by_id'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} physical inventory count")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function items()
    {
        return $this->hasMany(InventoryPhysicalCountItem::class, 'count_id');
    }

    public function countedBy()
    {
        return $this->belongsTo(User::class, 'counted_by_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
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

        static::creating(function ($count) {
            if (empty($count->count_no)) {
                $count->count_no = self::generateCountNo();
            }
        });
    }

    public static function generateCountNo(): string
    {
        $year   = date('Y');
        $prefix = "PC-{$year}-";

        $last = self::where('count_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(count_no, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('count_no');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
