<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCodeGroup extends Model
{
    protected $fillable = [
        'procurement_code_id',
        'procurement_id'
    ];

    public function procurement_code()
    {
        return $this->belongsTo('App\Models\ProcurementCode', 'procurement_code_id' );
    }

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id' );
    }

}
