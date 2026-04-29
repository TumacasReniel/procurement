<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProcurementPoDelivery extends Model
{
    protected $fillable = [
        'procurement_id',
        'po_id',
        'invoice_no',
        'invoice_date',
        'delivered_items',
        'received_by_id',
    ];

    protected $casts = [
        'delivered_items' => 'array',
        'invoice_date' => 'date',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function po()
    {
        return $this->belongsTo('App\Models\ProcurementNoaPo', 'po_id');
    }

    public function received_by()
    {
        return $this->belongsTo('App\Models\User', 'received_by_id')->with('profile');
    }

    public function normalizedDeliveredItems($noaItems): Collection
    {
        $availableItems = collect($noaItems)->keyBy(fn ($item) => (int) $item->id);
        $storedItems = collect($this->delivered_items ?? []);

        if ($storedItems->isEmpty()) {
            return collect();
        }

        return collect(static::normalizeDeliveredItemsForStorage($storedItems->all(), $availableItems->values()))
            ->map(function ($entry) use ($availableItems) {
                $itemId = (int) data_get($entry, 'item_id');
                $availableItem = $availableItems->get($itemId);

                return [
                    'item_id' => $itemId,
                    'ordered_quantity' => (float) data_get($availableItem, 'item.item.item_quantity', 0),
                    'delivered_quantity' => (float) data_get($entry, 'delivered_quantity', 0),
                ];
            })
            ->values();
    }

    public static function normalizeDeliveredItemsForStorage(array $selectedItems, $noaItems): array
    {
        $availableItems = collect($noaItems)->keyBy(fn ($item) => (int) $item->id);

        return collect($selectedItems)
            ->map(function ($entry) use ($availableItems) {
                $itemId = (int) data_get($entry, 'item_id');
                $availableItem = $availableItems->get($itemId);

                if (!$availableItem) {
                    return null;
                }

                $orderedQuantity = (float) data_get($availableItem, 'item.item.item_quantity', 0);
                $deliveredQuantity = (float) data_get($entry, 'delivered_quantity', $orderedQuantity);

                if ($itemId <= 0 || $orderedQuantity <= 0 || $deliveredQuantity <= 0) {
                    return null;
                }

                return [
                    'item_id' => $itemId,
                    'delivered_quantity' => static::normalizeQuantity(min($deliveredQuantity, $orderedQuantity)),
                ];
            })
            ->filter()
            ->unique('item_id')
            ->values()
            ->all();
    }

    protected static function normalizeQuantity($quantity)
    {
        $quantity = round((float) $quantity, 4);

        return floor($quantity) == $quantity ? (int) $quantity : $quantity;
    }
}
