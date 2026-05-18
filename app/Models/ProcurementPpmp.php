<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPpmp extends Model
{
    protected $fillable = [
        'request_id',
        'code',
        'date',
        'purpose',
        'title',
        'division_id',
        'unit_id',
        'fund_cluster_id',
        'classification_id',
        'reference_app_id',
        'created_by_id',
        'requested_by_id',
        'approved_by_id',
        'status_id',
        'sub_status_id',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function division()
    {
        return $this->belongsTo(ListDropdown::class, 'division_id');
    }

    public function unit()
    {
        return $this->belongsTo(ListUnit::class, 'unit_id');
    }

    public function fund_cluster()
    {
        return $this->belongsTo(ListDropdown::class, 'fund_cluster_id');
    }

    public function classification()
    {
        return $this->belongsTo(ListDropdown::class, 'classification_id');
    }

    public function reference_app()
    {
        return $this->belongsTo(ListDropdown::class, 'reference_app_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id')->with('profile');
    }

    public function requested_by()
    {
        return $this->belongsTo(User::class, 'requested_by_id')->with('profile');
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class, 'approved_by_id')->with('profile');
    }

    public function codes()
    {
        return $this->hasMany(ProcurementPpmpCodeGroup::class, 'procurement_ppmp_id')
            ->with('procurement_code', 'procurement_code.mode_of_procurement', 'procurement_code.app_type');
    }

    public function items()
    {
        return $this->hasMany(ProcurementPpmpItem::class, 'procurement_ppmp_id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }

    public function sub_status()
    {
        return $this->belongsTo(ListStatus::class, 'sub_status_id');
    }
}
