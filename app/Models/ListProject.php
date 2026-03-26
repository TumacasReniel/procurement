<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListProject extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type_id',
    ];

    public function type()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'type_id', 'id');
    }



}
