<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCodeBudgetLog extends Model
{
    protected $fillable = [
        'procurement_code_id',
        'procurement_id',
        'processed_by_id',
        'requested_by_id',
        'reviewed_by_id',
        'type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'attachment_name',
        'attachment_path',
        'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function procurement_code()
    {
        return $this->belongsTo(ProcurementCode::class, 'procurement_code_id');
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class, 'procurement_id');
    }

    public function processed_by()
    {
        return $this->belongsTo(User::class, 'processed_by_id')->with('profile');
    }

    public function requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by_id')->with('profile');
    }

    public function reviewed_by()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id')->with('profile');
    }
}
