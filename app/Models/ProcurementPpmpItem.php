<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProcurementPpmpItem extends Model
{
    use LogsActivity;

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
        'start_of_procurement_activity',
        'end_of_procurement_activity',
        'expected_delivery_date',
        'attached_supporting_documents',
        'supporting_document_path',
        'supporting_document_original_name',
        'remarks',
        'requested_quantity',
        'funded_quantity',
        'is_partial_funding',
        'item_quantity',
        'item_unit_cost',
        'price_basis',
        'price_basis_amount',
        'quantity_adjustment_reason',
        'price_variance_reason',
        'total_cost',
        'status_id',
    ];

    protected $casts = [
        'requested_quantity' => 'float',
        'funded_quantity' => 'float',
        'is_partial_funding' => 'boolean',
        'item_quantity' => 'float',
        'item_unit_cost' => 'float',
        'price_basis_amount' => 'float',
        'total_cost' => 'float',
    ];

    public function procurement()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function ppmp()
    {
        return $this->belongsTo(ProcurementPpmp::class, 'procurement_ppmp_id');
    }

    public function pr_items()
    {
        return $this->hasMany(ProcurementItem::class, 'ppmp_item_id');
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'item_no',
                'procurement_ppmp_id',
                'item_unit_type_id',
                'item_name',
                'item_description',
                'project_type',
                'item_category_id',
                'recommended_mode_of_procurement',
                'pre_procurement_conference',
                'start_of_procurement_activity',
                'end_of_procurement_activity',
                'expected_delivery_date',
                'attached_supporting_documents',
                'supporting_document_path',
                'supporting_document_original_name',
                'remarks',
                'requested_quantity',
                'funded_quantity',
                'is_partial_funding',
                'item_quantity',
                'item_unit_cost',
                'price_basis',
                'price_basis_amount',
                'quantity_adjustment_reason',
                'price_variance_reason',
                'total_cost',
                'status_id',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "PPMP item {$eventName}")
            ->useLogName('Procurement Plan Item')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
