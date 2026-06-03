<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryRisItem extends Model
{
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
