<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListDropdown extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'classification',
        'type',
        'color',
        'others',
        'is_active',
    ];

    public function designationable()
    {
        return $this->morphOne('App\Models\Signatory', 'designationable');
    }

    public function procurement_codes()
    {
        return $this->hasMany('App\Models\ProcurementCode', 'mode_of_procurement_id', 'id');
    }

    public static function getID($designation , $classification){
        $status = self::where('name', $designation)
            ->where(function ($query) use ($classification) {
                $query->where('classification', $classification)
                    ->orWhere('type', $classification);
            })
            ->first();

        return $status ? $status->id : null;
    }
}
