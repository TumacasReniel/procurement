<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryParItem extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'inventory_par_items';

    protected $fillable = [
        'par_id', 'item_id', 'property_no', 'date_acquired',
        'amount', 'quantity', 'description', 'remarks',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'amount'        => 'decimal:2',
        'date_acquired' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['par_id', 'item_id', 'property_no', 'quantity', 'amount', 'description'])
            ->setDescriptionForEvent(fn (string $e) => "{$e} PAR line item")
            ->useLogName('Inventory')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function par()  { return $this->belongsTo(InventoryPar::class, 'par_id'); }
    public function item() { return $this->belongsTo(InventoryItem::class, 'item_id'); }
}
