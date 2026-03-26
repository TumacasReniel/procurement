<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRequestTypeDocument extends Model
{

    protected $table = "request_type_documents";
    protected $fillable = [
        'request_type_id',
        'document_id',
    ];

    public function document()
    {
        return $this->belongsTo('App\Models\FinanceDocument', 'document_id');
    }


}
