<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementAssignment extends Model
{
    protected $fillable = [
        'procurement_id',
        'status',
        'user_id',
        'created_by_id',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class, 'procurement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
