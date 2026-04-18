<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','short','division_id'
    ];

    public function division()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'division_id', 'id');
    }

    public function responsibility_center()
    {
        return $this->hasOne(ResponsibilityCenter::class, 'list_unit_id');
    }

    public function getResponsibilityCenterCodeAttribute()
    {
        return $this->responsibility_center?->code;
    }
}
