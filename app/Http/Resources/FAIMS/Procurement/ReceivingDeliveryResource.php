<?php

namespace App\Http\Resources\FAIMS\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivingDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $procurement = $this->noa?->procurement_quotation?->procurement;
        $supplier = $this->noa?->procurement_quotation?->supplier;
        $summary = $this->getAttribute('delivery_monitoring_summary') ?? [];
        $items = collect($this->getAttribute('delivery_monitoring_items') ?? []);
        $monitoringItems = $items->keyBy(fn ($item) => (int) data_get($item, 'id'));
        $receivedDeliveries = $this->deliveries
            ? $this->deliveries->map(function ($delivery) use ($monitoringItems) {
                $deliveredItems = collect($delivery->delivered_items ?? [])
                    ->map(function ($entry) use ($monitoringItems) {
                        $itemId = (int) data_get($entry, 'item_id');
                        $monitoringItem = $monitoringItems->get($itemId);

                        return [
                            'item_id' => $itemId,
                            'item_no' => data_get($monitoringItem, 'item_no'),
                            'description' => data_get($monitoringItem, 'description') ?: data_get($monitoringItem, 'item_name'),
                            'delivered_quantity' => data_get($entry, 'delivered_quantity', 0),
                            'unit' => data_get($monitoringItem, 'unit'),
                        ];
                    })
                    ->filter(fn ($entry) => (int) data_get($entry, 'item_id') > 0)
                    ->values();

                return [
                    'id' => $delivery->id,
                    'code' => 'RCV-' . str_pad((string) $delivery->id, 6, '0', STR_PAD_LEFT),
                    'invoice_no' => $delivery->invoice_no,
                    'invoice_date' => $delivery->invoice_date?->format('M d, Y'),
                    'invoice_date_raw' => $delivery->invoice_date?->toDateString(),
                    'received_at' => $delivery->created_at?->format('M d, Y h:i A'),
                    'received_by' => data_get($delivery, 'received_by.profile.fullname')
                        ?: data_get($delivery, 'received_by.profile.full_name')
                        ?: data_get($delivery, 'received_by.name')
                        ?: data_get($delivery, 'received_by.username'),
                    'items_count' => $deliveredItems->count(),
                    'total_quantity' => $deliveredItems->sum(fn ($entry) => (float) data_get($entry, 'delivered_quantity', 0)),
                    'items' => $deliveredItems,
                ];
            })->values()
            : collect();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'noa_id' => $this->noa_id,
            'procurement_id' => $this->procurement_id,
            'procurement_code' => $procurement?->code,
            'procurement_title' => $procurement?->title,
            'supplier_name' => $supplier?->name,
            'status' => $this->status,
            'po_date' => $this->po_date?->format('M d, Y'),
            'date_of_delivery' => $this->date_of_delivery?->format('M d, Y'),
            'date_of_delivery_raw' => $this->date_of_delivery?->toDateString(),
            'place_of_delivery' => $this->place_of_delivery?->name,
            'delivery_term' => $this->delivery_term,
            'delivery_monitoring_summary' => $summary,
            'delivery_monitoring_items' => $items->values(),
            'received_deliveries' => $receivedDeliveries,
            'iars' => $this->iars,
            'latest_iar' => $this->iar,
            'remaining_items_count' => (int) data_get($summary, 'needs_delivery_items', 0),
            'delivered_items_count' => (int) data_get($summary, 'delivered_items', 0),
            'partial_items_count' => (int) data_get($summary, 'partial_items', 0),
            'total_items_count' => (int) data_get($summary, 'total_items', 0),
            'can_receive_delivery' => (int) data_get($summary, 'needs_delivery_items', 0) > 0,
            'can_edit_received_items' => $receivedDeliveries->isNotEmpty() && $this->iars->isEmpty() && !$this->iar,
        ];
    }
}
