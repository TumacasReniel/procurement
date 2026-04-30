<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $po = $this->po;
        $procurement = $po?->noa?->procurement_quotation?->procurement;
        $supplier = $po?->noa?->procurement_quotation?->supplier;
        $monitoringItems = collect($po?->getAttribute('delivery_monitoring_items') ?? [])
            ->keyBy(fn ($item) => (int) data_get($item, 'id'));
        $deliveredItems = collect($this->delivered_items ?? [])
            ->map(function ($entry) use ($monitoringItems) {
                $itemId = (int) data_get($entry, 'item_id');
                $monitoringItem = $monitoringItems->get($itemId);

                return [
                    'item_id' => $itemId,
                    'item_no' => data_get($monitoringItem, 'item_no'),
                    'description' => data_get($monitoringItem, 'description') ?: data_get($monitoringItem, 'item_name'),
                    'delivered_quantity' => $this->normalizeQuantity(data_get($entry, 'delivered_quantity', 0)),
                    'unit' => data_get($monitoringItem, 'unit'),
                ];
            })
            ->filter(fn ($entry) => (int) data_get($entry, 'item_id') > 0)
            ->values();

        return [
            'id' => $this->id,
            'code' => 'RCV-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'po_id' => $po?->id,
            'po_code' => $po?->code,
            'po_status' => $po?->status,
            'procurement_code' => $procurement?->code,
            'procurement_title' => $procurement?->title,
            'supplier_name' => $supplier?->name,
            'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date?->format('M d, Y'),
            'invoice_date_raw' => $this->invoice_date?->toDateString(),
            'received_at' => $this->created_at?->format('M d, Y h:i A'),
            'received_by' => data_get($this, 'received_by.profile.fullname')
                ?: data_get($this, 'received_by.profile.full_name')
                ?: data_get($this, 'received_by.name')
                ?: data_get($this, 'received_by.username'),
            'items_count' => $deliveredItems->count(),
            'total_quantity' => $this->normalizeQuantity(
                $deliveredItems->sum(fn ($entry) => (float) data_get($entry, 'delivered_quantity', 0))
            ),
            'items' => $deliveredItems,
            'po' => $po ? [
                'id' => $po->id,
                'code' => $po->code,
                'delivery_monitoring_items' => collect($po->getAttribute('delivery_monitoring_items') ?? [])->values(),
            ] : null,
            'can_edit_received_items' => $po
                && $po->iars->isEmpty()
                && !$po->iar,
        ];
    }

    protected function normalizeQuantity($quantity)
    {
        $quantity = round((float) $quantity, 4);

        return floor($quantity) == $quantity ? (int) $quantity : $quantity;
    }
}
