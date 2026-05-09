<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPpmpItem extends Model
{
    protected $fillable = [
        'item_no',
        'procurement_ppmp_id',
        'item_unit_type_id',
        'item_name',
        'item_description',
        'project_type',
        'item_category_id',
        'recommended_mode_of_procurement',
        'pre_procurement_conference',
        'end_of_procurement_activity',
        'expected_delivery_date',
        'attached_supporting_documents',
        'supporting_document_path',
        'supporting_document_original_name',
        'remarks',
        'item_quantity',
        'item_unit_cost',
        'total_cost',
        'status_id',
    ];

    public function procurement()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function ppmp()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function item_unit_type()
    {
        return $this->belongsTo(UnitType::class, 'item_unit_type_id');
    }

    public function item_category()
    {
        return $this->belongsTo(ListDropdown::class, 'item_category_id');
    }

    public function status()
    {
        return $this->belongsTo(ListStatus::class, 'status_id');
    }
}
