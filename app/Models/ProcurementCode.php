<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCode extends Model
{
     protected $fillable = [
        'title',
        'code',
        'allocated_budget',
        'remaining_budget',
        'year',
        'app_type_id',
        'mode_of_procurement_id'
    ];

    protected $casts = [
        'allocated_budget' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
    ];

    public function app_type()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'app_type_id' );
    }

    public function mode_of_procurement()
    {
        return $this->belongsTo('App\Models\ListDropdown', 'mode_of_procurement_id' );
    }

    public function end_users()
    {
        return $this->hasMany('App\Models\ProcurementCodeUnit', 'procurement_code_id' , 'id')->with('end_user');
    }

    public function budget_logs()
    {
        return $this->hasMany(ProcurementCodeBudgetLog::class, 'procurement_code_id');
    }


}
