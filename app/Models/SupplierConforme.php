<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierConforme extends Model
{
    protected $fillable = [
        'supplier_id',
        'name',
        'contact_no',
        'position',
        'is_active',
        'user_id',
    ];

    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->with('profile');
    }
}
