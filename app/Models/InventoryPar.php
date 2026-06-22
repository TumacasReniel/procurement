<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryPar extends Model
{
    use LogsActivity;

    protected $table = 'inventory_par';

    protected $fillable = [
        'par_no', 'par_date', 'fund_cluster',
        'received_by_id', 'issued_by_id', 'approved_by_id',
        'status_id', 'remarks',
    ];

    protected $casts = ['par_date' => 'date'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['par_no', 'par_date', 'received_by_id', 'issued_by_id', 'status_id'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} PAR")
            ->useLogName('Inventory')->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function items()      { return $this->hasMany(InventoryParItem::class, 'par_id'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by_id'); }
    public function issuedBy()   { return $this->belongsTo(User::class, 'issued_by_id'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by_id'); }
    public function status()     { return $this->belongsTo(ListStatus::class, 'status_id'); }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($par) {
            if (empty($par->par_no)) {
                $par->par_no = self::generateParNo();
            }
        });
    }

    public static function generateParNo(): string
    {
        $year = date('Y'); $prefix = "PAR-{$year}-";
        $last = self::where('par_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(par_no, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('par_no');
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) $next = (int) $m[1] + 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
