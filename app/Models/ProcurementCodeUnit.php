<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCodeUnit extends Model
{
    protected $fillable = [
        'procurement_code_id',
        'end_user_id'
    ];

    public function procurement_code()
    {
        return $this->belongsTo('App\Models\ProcurementCode', 'procurement_code_id' );
    }

    public function end_user()
    {
        return $this->belongsTo('App\Models\ListUnit', 'end_user_id' );
    }
}
