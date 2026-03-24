<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRequestAttachment extends Model
{
    protected $fillable = [
        'finance_request_id',
        'finance_document_id',
        'name',
        'path',
        'mime_type',
        'size',
        'uploaded_by_id',
        'verification_status',
        'verification_note',
        'verified_by_id',
        'verified_at',
    ];

    public function request()
    {
        return $this->belongsTo(FinanceRequest::class, 'finance_request_id');
    }

    public function document()
    {
        return $this->belongsTo(FinanceDocument::class, 'finance_document_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function comments()
    {
        return $this->morphMany(RequestComment::class, 'commentable')->latest();
    }
}
