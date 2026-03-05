<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAddress extends Model
{
    protected $fillable = [
        'supplier_id',
        'address',
    ];
}
