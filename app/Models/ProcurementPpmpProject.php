<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcurementPpmpProject extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'procurement_ppmp_id',
        'title',
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
        'project_total_budget' => 'float',
    ];

    public function ppmp()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'procurement_ppmp_id',
                'title',
                'project_type',
                'recommended_mode_of_procurement',
                'pre_procurement_conference',
                'start_of_procurement_activity',
                'end_of_procurement_activity',
                'expected_delivery_date',
                'attached_supporting_documents',
                'remarks',
                'project_total_budget',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "PPMP project {$eventName}")
            ->useLogName('Procurement Plan Project')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
