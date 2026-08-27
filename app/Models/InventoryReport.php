<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryReport extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'title_id',
        'category_id',
        'period_type',
        'period_year',
        'period_month',
        'period_quarter',
        'period_start',
        'period_end',
        'period_label',
        'created_by_id',
    ];

    protected $casts = [
        'title_id' => 'integer',
        'category_id' => 'integer',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'period_quarter' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'title_id', 'category_id', 'period_label'])
            ->setDescriptionForEvent(fn (string $e) => "{$e} inventory report")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function title()
    {
        return $this->belongsTo(InventoryReportTitle::class, 'title_id');
    }

    public function category()
    {
        return $this->belongsTo(ListDropdown::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->code)) {
                $report->code = self::generateReportCode();
            }
        });
    }

    public static function generateReportCode(): string
    {
        $year = date('Y');
        $prefix = "RPT-{$year}-";

        $last = self::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('code');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
