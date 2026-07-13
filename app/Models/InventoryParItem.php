<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryParItem extends Model
{
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

    public function par()  { return $this->belongsTo(InventoryPar::class, 'par_id'); }
    public function item() { return $this->belongsTo(InventoryItem::class, 'item_id'); }
}
