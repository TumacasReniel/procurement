<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReportTitle extends Model
{
    protected $fillable = [
        'name',
        'data_source',
        'category_id',
        'is_active',
    ];

    public const SOURCE_ITEMS_RECEIVED = 'items_received';

    public const SOURCE_ITEMS_WITHDRAWN = 'items_withdrawn';

    public const SOURCE_STOCKS_RECEIVED = 'stocks_received';

    public const SOURCE_STOCKS_WITHDRAWN = 'stocks_withdrawn';

    public const SOURCE_RIS_ISSUED = 'ris_issued';

    public const SOURCE_NONE = 'none';

    protected $casts = [
        'category_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ListDropdown::class, 'category_id');
    }
}
