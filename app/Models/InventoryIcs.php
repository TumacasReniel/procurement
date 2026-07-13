<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryIcs extends Model
{
    use LogsActivity;

    protected $table = 'inventory_ics';

    protected $fillable = [
        'ics_no', 'ics_date', 'fund_cluster',
        'issued_to_id', 'issued_by_id', 'approved_by_id',
        'status_id', 'remarks',
    ];

    protected $casts = ['ics_date' => 'date'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ics_no', 'ics_date', 'issued_to_id', 'issued_by_id', 'status_id'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} ICS")
            ->useLogName('Inventory')->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function items()     { return $this->hasMany(InventoryIcsItem::class, 'ics_id'); }
    public function issuedTo()  { return $this->belongsTo(User::class, 'issued_to_id'); }
    public function issuedBy()  { return $this->belongsTo(User::class, 'issued_by_id'); }
    public function approvedBy(){ return $this->belongsTo(User::class, 'approved_by_id'); }
    public function status()    { return $this->belongsTo(ListStatus::class, 'status_id'); }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ics) {
            if (empty($ics->ics_no)) {
                $ics->ics_no = self::generateIcsNo();
            }
        });
    }

    public static function generateIcsNo(): string
    {
        $year = date('Y'); $prefix = "ICS-{$year}-";
        $last = self::where('ics_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(ics_no, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('ics_no');
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) $next = (int) $m[1] + 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
