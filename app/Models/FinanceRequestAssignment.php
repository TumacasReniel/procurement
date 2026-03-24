<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRequestAssignment extends Model
{
    protected $fillable = [
        'finance_request_id',
        'status',
        'user_id',
        'created_by_id',
    ];

    public function finance_request()
    {
        return $this->belongsTo(FinanceRequest::class, 'finance_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
