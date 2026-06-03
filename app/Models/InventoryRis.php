<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRis extends Model
{
    use HasFactory;

    protected $table = 'inventory_ris';

    protected $fillable = [
        'ris_no',
        'fund_cluster',
        'division',
        'responsibility_center',
        'purpose',
        'ris_date',
        'requested_by_id',
        'approved_by_id',
        'issued_by_id',
        'received_by_id',
        'status_id',
    ];

    protected $casts = [
        'ris_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(InventoryRisItem::class, 'ris_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id')->with('profile');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id')->with('profile');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_id')->with('profile');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_id')->with('profile');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ris) {
            if (empty($ris->ris_no)) {
                $ris->ris_no = self::generateRisNo();
            }
        });
    }

    public static function generateRisNo(): string
    {
        $year = date('Y');
        $prefix = "RIS-{$year}-";

        $last = self::where('ris_no', 'like', $prefix . '%')
            ->orderByDesc('ris_no')
            ->value('ris_no');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
