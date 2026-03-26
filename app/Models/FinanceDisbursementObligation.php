<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceDisbursementObligation extends Model
{
    protected $fillable = [
        'os_number',
        'dv_number',
        'request_number',
        'payee',
        'fund_source',
        'amount',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
