<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRequestType extends Model
{
    protected $fillable = [
        'name',
        'default_text',
        'required_documents',
        'is_active',
    ];

    
    public function documents()
    {
        return $this->hasMany('App\Models\FinanceRequestTypeDocument', 'request_type_id')->with('document');
    } 







}
