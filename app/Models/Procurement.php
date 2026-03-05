<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Procurement extends Model
{
    use LogsActivity;
    protected $fillable = [
        'code',
        'date',
        'purpose',
        'title',
        'division_id',
        'unit_id',
        'fund_cluster_id',
        'created_by_id',
        'requested_by_id',
        'approved_by_id',
        'reawarded_count',
        'rebidded_count',
        'quotation_count',
        'status_id',
        'sub_status_id'
    ];

    public function division()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'division_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\ListUnit', 'unit_id', 'id');
    }

    public function fund_cluster()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'fund_cluster_id', 'id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'created_by_id')->with('profile');
    }


    public function requested_by()
    {
        return $this->belongsTo('App\Models\User', 'requested_by_id')->with('profile');
    }

    public function approved_by()
    {
        return $this->belongsTo('App\Models\User', 'approved_by_id')->with('profile');
    }

    public function codes()
    {
        return $this->hasMany('App\Models\ProcurementCodeGroup', 'procurement_id',)->with('procurement_code', 'procurement_code.mode_of_procurement' , 'procurement_code.app_type') ;
    }

    public function items()
    {
        return $this->hasMany('App\Models\ProcurementItem', 'procurement_id');
    }

    public function quotations()
    {
        return $this->hasMany('App\Models\ProcurementQuotation', 'procurement_id');
    }

    public function bac_resolutions()
    {
        return $this->hasMany('App\Models\ProcurementBac', 'procurement_id');
    }

    public function noas()
    {
        return $this->hasMany('App\Models\ProcurementBacNoa', 'procurement_id');
    }

    public function pos()
    {
        return $this->hasMany('App\Models\ProcurementNoaPo', 'procurement_id');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }

    public function sub_status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'sub_status_id');
    }

    public function comments()
    {
        return $this->morphMany('App\Models\RequestComment', 'commentable');
    }

    
    public static function generateProcurementNumber($date = null)
    {
        if ($date) {
            $year = date("y", strtotime($date));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime($date));
        } else {
            $year = date("y", strtotime("now"));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime("now"));
        }

        $count = self::whereYear('date', date("Y", strtotime($date ?? "now")))
                     ->whereMonth('date', $month)
                     ->count() + 1;

        return 'PR-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
        ->logOnly(['code','date','purpose','title','division_id','unit_id','fund_cluster_id','created_by_id','requested_by_id','approved_by_id','reawarded_count','rebidded_count','quotation_count','status_id','sub_status_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "Procurement {$eventName}")
        ->useLogName('Procurement')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
