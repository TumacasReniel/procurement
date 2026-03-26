<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class VisitorLogs extends Model
{
     use LogsActivity;

    protected $fillable = [
        'visitor_id',
        'am_in_at',
        'am_out_at',
        'pm_in_at',
        'pm_out_at',
        'remarks',
        'station_id',
        'date'
    ];

    protected $casts = [
        'am_in_at' => 'encrypted:json', 
        'am_out_at' => 'encrypted:json', 
        'pm_in_at' => 'encrypted:json', 
        'pm_out_at' => 'encrypted:json',
        'remarks' => 'encrypted:json',
    ];

    public function visitor()
    {
        return $this->belongsTo('App\Models\Visitor', 'visitor_id', 'id');
    }

    public function getUpdatedAtAttribute($value)
    {
        return date('M d, Y g:i a', strtotime($value));
    }

    public function getCreatedAtAttribute($value)
    {
        return date('F d, Y g:i a', strtotime($value));
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly([ 'visitor_id',
        'am_in_at',
        'am_out_at',
        'pm_in_at',
        'pm_out_at',
        'remarks',
        'date'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} the date time record")
        ->useLogName('Dtr')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }
}
