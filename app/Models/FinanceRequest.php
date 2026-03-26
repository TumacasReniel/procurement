<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FinanceRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'request_id',
        'code',
        'date',
        'request_type_id',
        'division_id',
        'fund_cluster_id',
        'creditor_id',
        'creditor_type',
        'obligation_type_id',
        'project_type_id',
        'project_id',
        'status_id',
        'created_by_id',
        'requested_by',
        'amount',
        'particulars',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateFinanceRequestNumber($model->date);
            }
            if (empty($model->status_id)) {
                $model->status_id = self::getDefaultStatusId();
            }
        });
    }

    public static function generateFinanceRequestNumber($date = null)
    {
        if ($date) {
            $year = date("y", strtotime($date));
            $month = date("m", strtotime($date));
        } else {
            $year = date("y");
            $month = date("m");
        }

        $count = self::whereYear('date', date("Y", strtotime($date ?? "now")))
            ->whereMonth('date', $month)
            ->count() + 1;

        return 'FIN-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public static function getDefaultStatusId()
    {
        $statusId = ListStatus::getID('Pending', 'Finance Request');
        if ($statusId) {
            return $statusId;
        }

        $status = ListStatus::where('classification', 'Finance Request')
            ->where('is_active', 1)
            ->first();

        return $status ? $status->id : null;
    }

    // Relationships
    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id', 'id');
    }

    public function division()
    {
        return $this->belongsTo(ListUnit::class, 'division_id', 'id');
    }

    public function request_type()
    {
        return $this->belongsTo(FinanceRequestType::class, 'request_type_id', 'id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function creditor()
    {
        return $this->morphTo();
    }

    public function assignments()
    {
        return $this->hasMany(\App\Models\FinanceRequestAssignment::class, 'finance_request_id');
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\FinanceRequestAttachment::class, 'finance_request_id');
    }

    public function comments()
    {
        return $this->morphMany(\App\Models\RequestComment::class, 'commentable');
    }

    public function requestType()
    {
        return $this->belongsTo(FinanceRequestType::class, 'request_type_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code',
                'date',
                'request_type_id',
                'division_id',
                'fund_cluster_id',
                'project_type_id',
                'project_id',
                'creditor_id',
                'creditor_type',
                'status_id',
                'requested_by',
                'amount',
                'particulars',
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "Finance Request {$eventName}")
            ->useLogName('Finance Request')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
