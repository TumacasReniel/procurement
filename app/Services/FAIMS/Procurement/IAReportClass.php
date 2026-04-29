<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ReceivingDeliveryResource;
use App\Http\Resources\FAIMS\Procurement\ReceivingRecordResource;
use App\Models\ListStatus;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoDelivery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class IAReportClass
{
    public function __construct(
        protected ProcurementPOClass $purchaseOrders
    ) {
    }

    public function lists($request)
    {
        $sort_direction = $request->sort === 'oldest' ? 'ASC' : 'DESC';
        $statusNames = $request->is('faims/ia-reports')
            ? [
                'Items Delivered',
                'PO Items Delivered',
                'Items Partially Delivered',
                'PO Items Partially Delivered',
                'Partially Delivered/For Inspection',
                'PO Delivered/For Inspection',
                'PO Partially Delivered/For Inspection',
            ]
            : [
                'Conformed',
                'Items Delivered',
                'PO Items Delivered',
            ];

        $receivableStatusIds = collect($statusNames)
            ->map(fn ($statusName) => ListStatus::getID($statusName, 'Procurement'))
            ->filter()
            ->values()
            ->all();

        $paginator = ProcurementNoaPo::query()
            ->with([
                'status',
                'iar.status',
                'iars.status',
                'iars.inspected_by.profile',
                'deliveries.received_by.profile',
                'place_of_delivery',
                'noa.procurement_quotation.supplier.address',
                'noa.procurement_quotation.procurement',
                'noa.items.item.item.item_unit_type',
            ])
            ->when(!empty($receivableStatusIds), function ($query) use ($receivableStatusIds) {
                $query->whereIn('status_id', $receivableStatusIds);
            })
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('noa', function ($noaQuery) use ($keyword) {
                            $noaQuery->where('code', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhereHas('noa.procurement_quotation.procurement', function ($procurementQuery) use ($keyword) {
                            $procurementQuery
                                ->where('code', 'LIKE', "%{$keyword}%")
                                ->orWhere('title', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhereHas('noa.procurement_quotation.supplier', function ($supplierQuery) use ($keyword) {
                            $supplierQuery->where('name', 'LIKE', "%{$keyword}%");
                        });
                });
            })
            ->orderByRaw("
                    LEFT(code, LOCATE('-', code) - 1) {$sort_direction},
                    CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) {$sort_direction}
            ")
            ->orderBy('created_at', $sort_direction)
            ->paginate($request->count ?? 10);

        $paginator->getCollection()->transform(function (ProcurementNoaPo $po) {
            $this->purchaseOrders->appendPurchaseOrderDeliveryAttributes($po);

            return $po;
        });

        return ReceivingDeliveryResource::collection($paginator);
    }

    public function selection($request)
    {
        $po = ProcurementNoaPo::with(
            'deliveries',
            'iars.status',
            'noa.items.item.item.item_unit_type'
        )->findOrFail($request->po_id);
        $isEditingReceivedItems = $request->boolean('edit_received_items');

        $savedDeliveries = $this->purchaseOrders->aggregateReceivedItems($po);
        $savedDeliveryMap = $savedDeliveries->keyBy('item_id');

        $items = $po->noa->items->map(function ($noaItem) use ($savedDeliveryMap, $isEditingReceivedItems) {
            $quotationItem = $noaItem->item;
            $procurementItem = $quotationItem?->item;
            $quantity = (float) ($procurementItem?->item_quantity ?? 0);
            $unitCost = (float) ($quotationItem?->bid_price ?? 0);
            $alreadyDeliveredQuantity = min(
                (float) data_get($savedDeliveryMap->get((int) $noaItem->id), 'delivered_quantity', 0),
                $quantity
            );
            $availableQuantity = max($quantity - $alreadyDeliveredQuantity, 0);

            if (!$isEditingReceivedItems && $availableQuantity <= 0) {
                return null;
            }

            if ($isEditingReceivedItems && $quantity <= 0) {
                return null;
            }

            return [
                'id' => (int) $noaItem->id,
                'item_no' => $procurementItem?->item_no,
                'description' => $procurementItem?->item_description,
                'quantity' => $this->normalizeQuantity($quantity),
                'ordered_quantity' => $this->normalizeQuantity($quantity),
                'already_delivered_quantity' => $isEditingReceivedItems
                    ? 0
                    : $this->normalizeQuantity($alreadyDeliveredQuantity),
                'available_quantity' => $this->normalizeQuantity(
                    $isEditingReceivedItems ? $quantity : $availableQuantity
                ),
                'delivered_quantity' => $this->normalizeQuantity(
                    $isEditingReceivedItems ? $alreadyDeliveredQuantity : $availableQuantity
                ),
                'is_selected' => $isEditingReceivedItems
                    ? $alreadyDeliveredQuantity > 0
                    : true,
                'unit' => $procurementItem?->item_unit_type?->name_short
                    ?? $procurementItem?->item_unit_type?->name_long
                    ?? '',
                'unit_cost' => $unitCost,
                'amount' => 0,
                'available_amount' => round($unitCost * $availableQuantity, 2),
                'ordered_amount' => round($unitCost * $quantity, 2),
            ];
        })->filter()->values();

        if ($items->isEmpty()) {
            $this->purchaseOrders->appendPurchaseOrderDeliveryAttributes($po);

            $items = collect($po->getAttribute('delivery_monitoring_items') ?? [])
                ->filter(function ($item) use ($isEditingReceivedItems) {
                    return $isEditingReceivedItems
                        ? (float) data_get($item, 'ordered_quantity', 0) > 0
                        : (float) data_get($item, 'remaining_quantity', 0) > 0;
                })
                ->map(function ($item) use ($isEditingReceivedItems) {
                    $orderedQuantity = (float) data_get($item, 'ordered_quantity', data_get($item, 'remaining_quantity', 0));
                    $deliveredQuantity = (float) data_get($item, 'delivered_quantity', 0);
                    $availableQuantity = $isEditingReceivedItems
                        ? $orderedQuantity
                        : (float) data_get($item, 'remaining_quantity', 0);

                    return [
                        'id' => (int) data_get($item, 'id'),
                        'item_no' => data_get($item, 'item_no'),
                        'description' => data_get($item, 'description') ?: data_get($item, 'item_name'),
                        'quantity' => $this->normalizeQuantity($orderedQuantity),
                        'ordered_quantity' => $this->normalizeQuantity($orderedQuantity),
                        'already_delivered_quantity' => $isEditingReceivedItems
                            ? 0
                            : $this->normalizeQuantity($deliveredQuantity),
                        'available_quantity' => $this->normalizeQuantity($availableQuantity),
                        'delivered_quantity' => $this->normalizeQuantity(
                            $isEditingReceivedItems ? $deliveredQuantity : $availableQuantity
                        ),
                        'is_selected' => $isEditingReceivedItems
                            ? $deliveredQuantity > 0
                            : true,
                        'unit' => data_get($item, 'unit', ''),
                        'unit_cost' => (float) data_get($item, 'unit_cost', 0),
                        'amount' => 0,
                        'available_amount' => round((float) data_get($item, 'unit_cost', 0) * $availableQuantity, 2),
                        'ordered_amount' => round((float) data_get($item, 'unit_cost', 0) * $orderedQuantity, 2),
                    ];
                })
                ->values();
        }

        return [
            'po_id' => $po->id,
            'po_code' => $po->code,
            'receiving_id' => null,
            'receiving_code' => 'To be recorded',
            'invoice_no' => null,
            'invoice_date' => null,
            'saved_item_ids' => $savedDeliveries->pluck('item_id')->all(),
            'selected_item_ids' => [],
            'delivered_items' => [],
            'items' => $items,
        ];
    }

    public function receivingRecords($request)
    {
        $sortDirection = $request->sort === 'oldest' ? 'ASC' : 'DESC';

        $paginator = ProcurementPoDelivery::query()
            ->with([
                'received_by.profile',
                'po.status',
                'po.iar.status',
                'po.iars.status',
                'po.noa.procurement_quotation.supplier.address',
                'po.noa.procurement_quotation.procurement',
                'po.noa.items.item.item.item_unit_type',
            ])
            ->when($request->keyword, function ($query, $keyword) {
                $normalizedKeyword = trim((string) $keyword);
                $deliveryId = (int) preg_replace('/\D+/', '', $normalizedKeyword);

                $query->where(function ($innerQuery) use ($normalizedKeyword, $deliveryId) {
                    $innerQuery
                        ->where('invoice_no', 'LIKE', "%{$normalizedKeyword}%")
                        ->orWhereHas('po', function ($poQuery) use ($normalizedKeyword) {
                            $poQuery->where('code', 'LIKE', "%{$normalizedKeyword}%")
                                ->orWhereHas('noa.procurement_quotation.procurement', function ($procurementQuery) use ($normalizedKeyword) {
                                    $procurementQuery
                                        ->where('code', 'LIKE', "%{$normalizedKeyword}%")
                                        ->orWhere('title', 'LIKE', "%{$normalizedKeyword}%");
                                })
                                ->orWhereHas('noa.procurement_quotation.supplier', function ($supplierQuery) use ($normalizedKeyword) {
                                    $supplierQuery->where('name', 'LIKE', "%{$normalizedKeyword}%");
                                });
                        });

                    if ($deliveryId > 0) {
                        $innerQuery->orWhereKey($deliveryId);
                    }
                });
            })
            ->orderBy('created_at', $sortDirection)
            ->paginate($request->count ?? 10);

        $paginator->getCollection()->each(function (ProcurementPoDelivery $delivery) {
            if ($delivery->po) {
                $this->purchaseOrders->appendPurchaseOrderDeliveryAttributes($delivery->po);
            }
        });

        return ReceivingRecordResource::collection($paginator);
    }

    public function receive($id, $request): array
    {
        $po = ProcurementNoaPo::with('deliveries', 'iars', 'noa.items.item.item')->findOrFail($id);
        $isEditingReceivedItems = $request->boolean('edit_received_items');
        $invoiceNo = trim((string) $request->input('invoice_no', ''));
        $invoiceNo = $invoiceNo !== '' ? $invoiceNo : null;
        $invoiceDate = $request->filled('invoice_date') ? $request->input('invoice_date') : null;

        if ($isEditingReceivedItems && $po->iars->isNotEmpty()) {
            return [
                'data' => null,
                'message' => 'Received items can no longer be edited.',
                'info' => 'This Purchase Order already has a generated IAR.',
                'errors' => ['delivered_items' => 'Received items can no longer be edited after an IAR is generated.'],
                'status' => false,
            ];
        }

        if ($invoiceDate && !strtotime($invoiceDate)) {
            return [
                'data' => null,
                'message' => 'Invalid invoice date.',
                'info' => 'Please enter a valid invoice date before receiving delivered items.',
                'errors' => ['invoice_date' => 'Please enter a valid invoice date.'],
                'status' => false,
            ];
        }

        $availableItems = $po->noa->items->keyBy(fn ($item) => (int) $item->id);
        $existingDeliveries = $isEditingReceivedItems
            ? collect()
            : $this->purchaseOrders->aggregateReceivedItems($po)->keyBy('item_id');
        $requestedDeliveries = collect($request->input('delivered_items', []))->values();

        if ($requestedDeliveries->isEmpty()) {
            return [
                'data' => null,
                'message' => 'No delivered item selected.',
                'info' => 'Please select at least one delivered item to receive.',
                'errors' => ['delivered_items' => 'Please select at least one delivered item to receive.'],
                'status' => false,
            ];
        }

        $validatedDeliveries = [];
        $validationErrors = [];

        foreach ($requestedDeliveries as $index => $delivery) {
            $itemId = (int) data_get($delivery, 'item_id');
            $availableItem = $availableItems->get($itemId);

            if (!$availableItem) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] = 'This item does not belong to the selected Purchase Order.';
                continue;
            }

            $orderedQuantity = (float) data_get($availableItem, 'item.item.item_quantity', 0);
            $alreadyDeliveredQuantity = $isEditingReceivedItems
                ? 0
                : min(
                    (float) data_get($existingDeliveries->get($itemId), 'delivered_quantity', 0),
                    $orderedQuantity
                );
            $availableQuantity = $isEditingReceivedItems
                ? $orderedQuantity
                : max($orderedQuantity - $alreadyDeliveredQuantity, 0);
            $deliveredQuantity = (float) data_get($delivery, 'delivered_quantity', 0);

            if ($deliveredQuantity <= 0) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] = 'Received quantity must be greater than zero.';
                continue;
            }

            if ($deliveredQuantity > $availableQuantity) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'Received quantity must not exceed ' . $this->normalizeQuantity($availableQuantity) . '.';
                continue;
            }

            $validatedDeliveries[$itemId] = [
                'item_id' => $itemId,
                'delivered_quantity' => $this->normalizeQuantity($deliveredQuantity),
            ];
        }

        if (!empty($validationErrors)) {
            return [
                'data' => null,
                'message' => 'Invalid received quantity.',
                'info' => 'Please correct the received item quantities before saving.',
                'errors' => array_merge(
                    ['delivered_items' => 'Please correct the received item quantities before saving.'],
                    $validationErrors
                ),
                'status' => false,
            ];
        }

        if ($isEditingReceivedItems) {
            ProcurementPoDelivery::query()
                ->where('po_id', $po->id)
                ->delete();
        }

        $delivery = ProcurementPoDelivery::create([
            'procurement_id' => $po->procurement_id,
            'po_id' => $po->id,
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate,
            'delivered_items' => array_values($validatedDeliveries),
            'received_by_id' => Auth::id(),
        ]);

        $po->load('status', 'noa.status', 'deliveries', 'noa.items.item.item.item_unit_type');
        $this->purchaseOrders->appendPurchaseOrderDeliveryAttributes($po);

        $summary = $po->getAttribute('delivery_monitoring_summary') ?? [];

        if ((int) data_get($summary, 'total_items', 0) > 0 && (int) data_get($summary, 'needs_delivery_items', 0) === 0) {
            $poUpdateData = [
                'status_id' => ListStatus::getID('Items Delivered', 'Procurement'),
            ];

            if (Schema::hasColumn($po->getTable(), 'actual_delivery_date')) {
                $poUpdateData['actual_delivery_date'] = now()->toDateString();
            }

            ProcurementNoaPo::query()
                ->whereKey($po->id)
                ->update($poUpdateData);

            $po->noa?->update([
                'status_id' => ListStatus::getID('PO Items Delivered', 'Procurement'),
            ]);
        }

        return [
            'data' => [
                'delivery_id' => $delivery->id,
                'invoice_no' => $delivery->invoice_no,
                'invoice_date' => $delivery->invoice_date?->toDateString(),
                'delivered_items' => $delivery->delivered_items ?? [],
            ],
            'message' => 'Delivered items received successfully!',
            'info' => $isEditingReceivedItems
                ? "You've successfully updated the received PO items."
                : "You've successfully recorded this delivery batch.",
            'status' => 'success',
        ];
    }

    protected function normalizeQuantity($quantity)
    {
        $quantity = round((float) $quantity, 4);

        return floor($quantity) == $quantity ? (int) $quantity : $quantity;
    }
}
