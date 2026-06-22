<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryRisItem extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ris_id', 'item_id', 'quantity_requested', 'quantity_issued', 'remarks'])
            ->setDescriptionForEvent(fn(string $e) => "{$e} RIS line item")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected $table = 'inventory_ris_items';

    protected $fillable = [
        'ris_id',
        'item_id',
        'unit_of_issue',
        'quantity_requested',
        'quantity_issued',
        'remarks',
    ];

    public function ris()
    {
        return $this->belongsTo(InventoryRis::class, 'ris_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
