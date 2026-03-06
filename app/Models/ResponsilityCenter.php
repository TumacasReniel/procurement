<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsilityCenter extends Model
{
    protected $fillable = [
        'list_unit_id',
        'code',
    ];

    public function unit()
    {
        return $this->belongsTo(ListUnit::class, 'list_unit_id');
    }

}
