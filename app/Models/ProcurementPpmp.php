<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcurementPpmp extends Model
{
    use LogsActivity;

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
        'procurement_app_id',
        'created_by_id',
        'requested_by_id',
        'submitted_by_id',
        'submitted_at',
        'reviewed_by_id',
        'reviewed_at',
        'approved_by_id',
        'approved_at',
        'consolidated_by_id',
        'consolidated_at',
        'status_id',
        'sub_status_id',
        'ppmp_type',
        'ppmp_type_version',
        'is_supplemental',
        'attachment_path',
        'attachment_original_name',
        'project_type',
        'recommended_mode_of_procurement',
        'pre_procurement_conference',
        'start_of_procurement_activity',
        'end_of_procurement_activity',
        'expected_delivery_date',
        'attached_supporting_documents',
        'remarks',
        'project_total_budget',
    ];

    protected $casts = [
        'is_supplemental' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'consolidated_at' => 'datetime',
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

    public function procurement_app()
    {
        return $this->belongsTo(ProcurementApp::class, 'procurement_app_id');
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

    public function reviewed_by(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            Schema::hasColumn($this->getTable(), 'reviewed_by_id') ? 'reviewed_by_id' : 'approved_by_id'
        )->with('profile');
    }

    public function submitted_by(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            Schema::hasColumn($this->getTable(), 'submitted_by_id') ? 'submitted_by_id' : 'requested_by_id'
        )->with('profile');
    }

    public function consolidated_by(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            Schema::hasColumn($this->getTable(), 'consolidated_by_id') ? 'consolidated_by_id' : 'approved_by_id'
        )->with('profile');
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

    public function comments()
    {
        return $this->morphMany(RequestComment::class, 'commentable');
    }

    public function latest_comment()
    {
        return $this->morphOne(RequestComment::class, 'commentable')->latestOfMany();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
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
                'procurement_app_id',
                'created_by_id',
                'requested_by_id',
                'reviewed_by_id',
                'approved_by_id',
                'status_id',
                'sub_status_id',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "PPMP {$eventName}")
            ->useLogName('Procurement Plan')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
