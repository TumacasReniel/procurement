<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcurementPpmpCodeGroup extends Model
{
    use LogsActivity;

    protected $fillable = [
        'procurement_code_id',
        'procurement_ppmp_id',
    ];

    public function procurement_code()
    {
        return $this->belongsTo(ProcurementCode::class, 'procurement_code_id');
    }

    public function ppmp()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'procurement_code_id',
                'procurement_ppmp_id',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "PPMP procurement code {$eventName}")
            ->useLogName('Procurement Plan Code')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
