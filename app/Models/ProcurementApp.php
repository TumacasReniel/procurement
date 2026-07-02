<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcurementApp extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'year',
        'version',
        'plan_phase',
        'pricing_overrides',
        'title',
        'app_type_id',
        'created_by_id',
        'requested_by_id',
        'submitted_by_id',
        'reviewed_by_id',
        'approved_by_id',
        'status_id',
        'sub_status_id',
    ];

    protected $casts = [
        'pricing_overrides' => 'array',
    ];

    public function app_type()
    {
        return $this->belongsTo(ListDropdown::class, 'app_type_id');
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

    public function reviewed_by()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id')->with('profile');
    }

    public function submitted_by()
    {
        return $this->belongsTo(User::class, 'submitted_by_id')->with('profile');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }

    public function source_ppmps()
    {
        return $this->hasMany(ProcurementPpmp::class, 'procurement_app_id');
    }

    public function comments()
    {
        return $this->morphMany(RequestComment::class, 'commentable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code',
                'year',
                'version',
                'title',
                'app_type_id',
                'created_by_id',
                'requested_by_id',
                'reviewed_by_id',
                'approved_by_id',
                'status_id',
                'sub_status_id',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "APP {$eventName}")
            ->useLogName('APP')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
