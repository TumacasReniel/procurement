<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'requested_by_id',
        'approved_by_id',
        'status_id',
        'released_at',
        'remarks',
    ];

    protected $casts = [
        'released_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id')->with('profile');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id')->with('profile');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }
}
