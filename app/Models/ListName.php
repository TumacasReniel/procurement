<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ListName extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name','type'
    ];

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly([
            'name','type'
        ])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}")
        ->useLogName('Name')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }
}
