<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementAssignment extends Model
{
    protected $fillable = [
        'status',
        'user_id',
        'created_by_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
