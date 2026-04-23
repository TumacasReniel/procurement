<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class procurementPoIar extends Model
{
    protected $fillable = [
        'procurement_id',
        'po_id',
        'code',
        'invoice_no',
        'invoice_date',
        'selected_item_ids',
        'status_id',
        'inspected_by_id',
    ];

    protected $casts = [
        'selected_item_ids' => 'array',
        'invoice_date' => 'date',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function po()
    {
        return $this->belongsTo('App\Models\ProcurementNoaPo', 'po_id')->with('noa');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }

    public function inspected_by()
    {
        return $this->belongsTo('App\Models\User', 'inspected_by_id')->with('profile');
    }

    public function normalizedDeliveredItems($noaItems): Collection
    {
        $availableItems = collect($noaItems)->keyBy(fn ($item) => (int) $item->id);
        $storedItems = collect($this->selected_item_ids ?? []);

        if ($storedItems->isEmpty()) {
            return collect();
        }

        $payload = $storedItems->map(function ($entry) {
            return is_array($entry) ? $entry : ['item_id' => $entry];
        })->all();

        return collect(static::normalizeDeliveredItemsForStorage($payload, $availableItems->values()))
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
                    'delivered_quantity' => static::normalizeQuantity(
                        min($deliveredQuantity, $orderedQuantity)
                    ),
                ];
            })
            ->filter()
            ->unique('item_id')
            ->values()
            ->all();
    }

    public static function generateIARNumber()
    {
        $now = now(); // Laravel's Carbon instance
        $year = $now->format('y'); // Last two digits of year
        $month = $now->format('m');

        // Count existing RFQs for this year and month
        $count = self::whereYear('created_at', $now->year)
                    ->whereMonth('created_at', $month)
                    ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "IAR-{$year}-{$month}-{$sequence}";
    }

    protected static function normalizeQuantity($quantity)
    {
        $quantity = round((float) $quantity, 4);

        return floor($quantity) == $quantity ? (int) $quantity : $quantity;
    }
}
