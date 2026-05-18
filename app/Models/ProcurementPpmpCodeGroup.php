<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPpmpCodeGroup extends Model
{
    protected $fillable = [
        'procurement_code_id',
        'procurement_ppmp_id',
    ];

    public function procurement_code()
    {
        return $this->belongsTo(ProcurementCode::class, 'procurement_code_id');
    }

    public function ppmp()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }
}
