<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAttachment extends Model
{
    protected $fillable = [
        'supplier_id',
        'type_id',
        'code',
        'name',
        'path',
    ];
}
