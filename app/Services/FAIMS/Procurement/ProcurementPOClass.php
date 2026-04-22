<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\ProcurementBac;
use App\Models\Procurement;
use App\Models\ProcurementItem;
use App\Models\ProcurementBacNoa;
use App\Models\ProcurementBacNoaItem;
use App\Models\ProcurementQuotation;
use App\Models\ProcurementQuotationItem;
use App\Models\ProcurementNoaPo;
use App\Models\ProcurementPoNtp;
use App\Models\ProcurementPoIar;
use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Models\ListDropdown;
use App\Models\OrgChart;
use App\Http\Resources\FAIMS\Procurement\ProcurementNoaPoResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\ListStatus;

class ProcurementPOClass
{
    public function lists($request)
    {
        $data = ProcurementNoaPoResource::collection(
            ProcurementNoaPo::with('noa')
                ->when($request->procurement_id, function ($query, $procurement_id) {
                    $query->where('procurement_id', $procurement_id);
                })
                ->when($request->keyword, function ($query) use ($request) {
                    $keyword = $request->keyword;

                    $query->where(function ($q) use ($keyword) {
                        $q->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('noa', function ($noaQ) use ($keyword) {
                            $noaQ->where('code', 'LIKE', "%{$keyword}%");
                        });
                    });
                })
                ->when($request->status, function ($query) use ($request) {
                    $query->where('status_id', ListStatus::getID($request->status, 'Procurement'));
              
                })
                ->orderBy('created_at','DESC')
                ->paginate($request->count)
        );

        return $data;
    }


    public function purchase_order($request){
        $data = ProcurementNoaPo::with(
            'status',
            'iar.status',
            'iar.inspected_by.profile',
            'iars.status',
            'iars.inspected_by.profile',
            'ntp',
            'place_of_delivery',
            'noa.procurement_quotation.supplier.address',
            'noa.procurement_quotation.procurement',
            'noa.items.item.item.item_unit_type'
        )->where('noa_id', $request->noa_id)->first();

        if ($data) {
            $this->appendIarSummaries($data);
            $deliveryMonitoring = $this->buildDeliveryMonitoringData($data);
            $data->setAttribute('delivery_monitoring_items', $deliveryMonitoring['items']);
            $data->setAttribute('delivery_monitoring_summary', $deliveryMonitoring['summary']);
            $data->setAttribute('resolved_actual_delivery_date', $deliveryMonitoring['actual_delivery_date']);
            $data->setAttribute('resolved_actual_delivery_date_display', $deliveryMonitoring['actual_delivery_date_display']);
        }

        return $data;
    }

    public function iarSelection($request)
    {
        $po = ProcurementNoaPo::with(
            'iar.status',
            'iars.status',
            'noa.items.item.item.item_unit_type'
        )->findOrFail($request->po_id);

        $iarId = (int) $request->input('iar_id');
        $editingIar = $iarId > 0 ? $po->iars->firstWhere('id', $iarId) : null;
        $currentIarDeliveries = $editingIar
            ? $editingIar->normalizedDeliveredItems($po->noa->items)->keyBy('item_id')
            : collect();

        $savedDeliveries = $this->aggregateDeliveredItems($po, $editingIar?->id);
        $savedDeliveryMap = $savedDeliveries->keyBy('item_id');
        $hasSavedDeliveries = $savedDeliveries->isNotEmpty();

        $items = $po->noa->items->map(function ($noaItem) use (
            $savedDeliveryMap,
            $hasSavedDeliveries,
            $editingIar,
            $currentIarDeliveries
        ) {
            $quotationItem = $noaItem->item;
            $procurementItem = $quotationItem?->item;
            $quantity = (float) ($procurementItem?->item_quantity ?? 0);
            $unitCost = (float) ($quotationItem?->bid_price ?? 0);
            $savedDelivery = $savedDeliveryMap->get((int) $noaItem->id);
            $currentIarDelivery = $currentIarDeliveries->get((int) $noaItem->id);
            $alreadyDeliveredQuantity = min(
                (float) data_get($savedDelivery, 'delivered_quantity', 0),
                $quantity
            );
            $availableQuantity = max($quantity - $alreadyDeliveredQuantity, 0);

            if ($availableQuantity <= 0 && !$currentIarDelivery) {
                return null;
            }

            $isSelected = $editingIar
                ? $currentIarDelivery !== null
                : !$hasSavedDeliveries;
            $deliveredQuantity = $currentIarDelivery
                ? (float) data_get($currentIarDelivery, 'delivered_quantity', 0)
                : ($isSelected ? $availableQuantity : 0);

            return [
                'id' => (int) $noaItem->id,
                'item_no' => $procurementItem?->item_no,
                'description' => $procurementItem?->item_description,
                'quantity' => $this->normalizeQuantity($quantity),
                'ordered_quantity' => $this->normalizeQuantity($quantity),
                'already_delivered_quantity' => $this->normalizeQuantity($alreadyDeliveredQuantity),
                'available_quantity' => $this->normalizeQuantity($availableQuantity),
                'delivered_quantity' => $this->normalizeQuantity($deliveredQuantity),
                'is_selected' => $isSelected,
                'unit' => $procurementItem?->item_unit_type?->name_short
                    ?? $procurementItem?->item_unit_type?->name_long
                    ?? '',
                'unit_cost' => $unitCost,
                'amount' => round($unitCost * $deliveredQuantity, 2),
                'available_amount' => round($unitCost * $availableQuantity, 2),
                'ordered_amount' => round($unitCost * $quantity, 2),
            ];
        })->filter()->values();

        $currentDeliveries = $items
            ->filter(fn ($item) => $item['is_selected'])
            ->map(fn ($item) => [
                'item_id' => (int) $item['id'],
                'delivered_quantity' => $this->normalizeQuantity($item['delivered_quantity']),
            ])
            ->values();

        return [
            'po_id' => $po->id,
            'po_code' => $po->code,
            'iar_id' => $editingIar?->id,
            'iar_code' => $editingIar?->code,
            'invoice_no' => $editingIar?->invoice_no,
            'invoice_date' => $editingIar?->invoice_date?->toDateString(),
            'saved_item_ids' => $savedDeliveries->pluck('item_id')->all(),
            'selected_item_ids' => $currentDeliveries->pluck('item_id')->all(),
            'delivered_items' => $currentDeliveries->all(),
            'items' => $items,
        ];
    }

    public function save($request)
    { 
        $user = Auth::user();
        $poDate = $request->filled('po_date') ? $request->po_date : now()->toDateString();
        $code = ProcurementNoaPo::generatePONumber($poDate);

        $data = ProcurementNoaPo::create([
            'procurement_id' => $request->procurement_id,
            'code' => $code,
            'po_date' => $poDate,
            'payment_term' => $request->payment_term,
            'delivery_term' => $request->delivery_term,
            'noa_id' => $request->noa_id,
            'place_of_delivery_id' => $request->place_of_delivery_id,
            'date_of_delivery' => $request->date_of_delivery,
            'created_by_id' => $user->id,
            'status_id' => ListStatus::getID('Created','Procurement'), // set to "Created"
        ]);

        $noa = ProcurementBacNoa::with('procurement_bac.procurement')->findOrFail($request->noa_id);
        $procurement = $noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;
        
        if($noa){
             // update PR status to "PO Created" 
             $noa->status_id = ListStatus::getID('PO Created','Procurement');
             $noa->update();

            // update PR status to "PO Created" 
            $procurement =  $noa->procurement_bac->procurement;
            if( $procurement->status_id == ListStatus::getID('Rebid','Procurement') || $procurement->status_id == ListStatus::getID('Re-award','Procurement')){
                $updated_pr_substatus = $noa->procurement_bac->overall_substatus($current_pr_status);
                // update Procurement Request SubStatus (only if we get a valid status)
                if($updated_pr_substatus !== null && is_numeric($updated_pr_substatus)){
                    $procurement->update([
                        'sub_status_id' =>  $updated_pr_substatus,
                    ]);
                }
            }
            else{
                $updated_pr_status = $noa->procurement_bac->overall_status($current_pr_status);
                // update Procurement Request Status (only if we get a valid status)
                if($updated_pr_status !== null && is_numeric($updated_pr_status)){
                    $procurement->update([
                        'status_id' =>  $updated_pr_status,
                        'sub_status_id'=>null,
                    ]);
                }
            }
   
          
  
        }

      



        return [
            'data' =>new ProcurementNoaPoResource($data),
            'message' => 'BAC Resolution created successfully!', 
            'info' => "You've successfully added new BAC Resolution.",
        ];
    }

    
    public function update($id, $request)
    { 
        $user = Auth::user();
        $data = ProcurementNoaPo::findOrFail($id);

        $data->update([
            'po_date' => $request->po_date,
            'payment_term' => $request->payment_term,
            'delivery_term' => $request->delivery_term,
            'place_of_delivery_id' => $request->place_of_delivery_id,
            'date_of_delivery' => $request->date_of_delivery,
            'updated_by_id' => $user->id,
        ]);

        return [
            'data' =>new ProcurementNoaPoResource($data),
            'message' => 'Purchase Order updated successfully!', 
            'info' => "You've successfully updated the Purchase Order.",
            'status' => 'success',
        ];
    }

    public function updateNTP($id, $request)
    {
        $user = Auth::user();
        $po = ProcurementNoaPo::with(
            'status',
            'ntp',
            'place_of_delivery',
            'noa.procurement_quotation.supplier.address',
            'noa.procurement_quotation.procurement',
            'noa.items.item.item.item_unit_type'
        )->findOrFail($id);

        if ($po->status?->name !== 'Conformed') {
            return [
                'data' => $po,
                'message' => 'Notice to Proceed can no longer be edited.',
                'info' => 'Only purchase orders with Conformed status can edit the Notice to Proceed.',
                'status' => 'warning',
            ];
        }

        if (!$po->ntp) {
            $this->createNTP($request, $po, $user);
            $po->load('ntp');
        }

        $body = $request->input('ntp_body', $request->input('body', $request->input('remarks')));

        $po->ntp->update([
            'remarks' => $body,
            'updated_by_id' => $user->id,
        ]);

        return [
            'data' => $po->fresh([
                'status',
                'ntp',
                'place_of_delivery',
                'noa.procurement_quotation.supplier.address',
                'noa.procurement_quotation.procurement',
                'noa.items.item.item.item_unit_type',
            ]),
            'message' => 'Notice to Proceed updated successfully!',
            'info' => "You've successfully updated the Notice to Proceed.",
            'status' => 'success',
        ];
    }

       
       
    public function updateStatus($id, $request)
    { 
 
        $user = Auth::user();
        $po = ProcurementNoaPo::with(
            'status',
            'iar.status',
            'iars.status',
            'noa.status',
            'noa.procurement_bac.procurement.items',
            'noa.items.item.item.item_unit_type'
        )->findOrFail($id);
        $current_pr_status = $po->noa->procurement_bac->procurement->status_id;
        $procurement =  $po->noa->procurement_bac->procurement;
        $statusPayload = $request->input('status');
        $requestedStatusName = is_array($statusPayload)
            ? ($statusPayload['name'] ?? null)
            : (is_object($statusPayload) ? ($statusPayload->name ?? null) : null);
        $currentStatusName = $this->normalizePurchaseOrderStatusName($requestedStatusName);

        if (!$currentStatusName) {
            return [
                'data' => new ProcurementNoaPoResource($po),
                'message' => 'Missing status payload.',
                'info' => 'No status update was applied.',
                'status' => 'warning',
            ];
        }

        // Update PO/NOA status FIRST based on the requested status
        if($currentStatusName == "Created"){

             $po->update([
                'released_at' => now(),
                'conformed_at' => null,
                'status_id' => ListStatus::getID('Issued','Procurement'), // set status to "Issued"
            ]);
            $po->noa->update([
                'status_id' => ListStatus::getID('PO Issued','Procurement'), // set noa status to "PO Issued"
            ]);
        }
        else if($currentStatusName == "Issued"){
        
       
            $po->update([
                'conformed_at' => now(),
                'status_id' => ListStatus::getID('Conformed','Procurement'), // set status to "Conformed"  
            ]);

            $po->noa->update([
                'status_id' => ListStatus::getID('PO Conformed','Procurement'), // set noa status to "PO Conformed"
            ]);

            // create Notice to Proceed(NTP) 
            $this->createNTP($request, $po, $user);
        }
        else if($currentStatusName == "Conformed"){
            $deliveryMonitoring = $this->buildDeliveryMonitoringData($po);

            if (($deliveryMonitoring['summary']['needs_delivery_items'] ?? 0) > 0) {
                return [
                    'data' => new ProcurementNoaPoResource($po),
                    'message' => 'Purchase Order cannot be marked as delivered yet.',
                    'info' => 'All items listed in the Purchase Order must be delivered before the PO status can be updated to Items Delivered.',
                    'status' => 'warning',
                ];
            }

            $poUpdateData = [
                'status_id' => ListStatus::getID('Items Delivered','Procurement'), // set status to "Items Delivered"
            ];

            if ($this->supportsActualDeliveryDateColumn()) {
                $poUpdateData['actual_delivery_date'] = $request->input(
                    'actual_delivery_date',
                    $this->resolveActualDeliveryDate($po)?->toDateString() ?? now()->toDateString()
                );
            }

            $po->update($poUpdateData);
            $po->noa->update([
                'status_id' => ListStatus::getID('PO Items Delivered','Procurement'), // set noa status to "PO Items Delivered"
            ]);
        }
        else if($currentStatusName == "Items Delivered"){
            if ($po->iars->isEmpty()) {
                return [
                    'data' => new ProcurementNoaPoResource($po),
                    'message' => 'No generated IAR report found.',
                    'info' => 'Generate at least one IAR report before completing inspection and acceptance.',
                    'status' => 'warning',
                ];
            }

            if ($this->hasPendingIars($po)) {
                return [
                    'data' => new ProcurementNoaPoResource($po),
                    'message' => 'Generated IAR reports must be completed first.',
                    'info' => 'Update each generated IAR report to Inspected/Completed before the Purchase Order can be marked as Completed.',
                    'status' => 'warning',
                ];
            }

            $this->finalizeCompletedPurchaseOrder($po, $procurement, $current_pr_status);
        }

        // Update PR status AFTER PO/NOA status has been updated
        // Refresh the NOA to get the updated status
        $po->refresh();
        $po->noa->refresh();
        
        // Use the NEW NOA status to determine PR status
        $new_noa_status = $po->noa->status->name;
        
        // Skip PR status update if NOA status is "Completed" (already handled above)
        if($new_noa_status != 'Completed'){
            // if current_pr_status "Re-award" or "Rebid"
            if($current_pr_status == ListStatus::getID('Re-award','Procurement') || $current_pr_status == ListStatus::getID('Rebid','Procurement')){
                $updated_pr_substatus = $po->noa->procurement_bac->overall_substatus($current_pr_status);
                // update Procurement Request SubStatus
                $procurement->update([
                    'sub_status_id' =>  $updated_pr_substatus,
                ]);

            }
            else{
                $updated_pr_status = $po->noa->procurement_bac->overall_status($current_pr_status);
                // update Procurement Request Status
                $procurement->update([
                    'status_id' =>  $updated_pr_status,
                    'sub_status_id'=>null,
                ]);
            }
        }

        return [
            'data' =>new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status updated successfully!', 
            'info' => "You've successfully updated Purchase Order Status.",
        ];
    }

    public function updateIARSelection($id, $request)
    {
        $po = ProcurementNoaPo::with('iar.status', 'iars.status', 'noa.items.item.item')->findOrFail($id);
        $iarId = (int) $request->input('iar_id');
        $editingIar = $iarId > 0 ? $po->iars->firstWhere('id', $iarId) : null;
        $invoiceNo = trim((string) $request->input('invoice_no', ''));
        $invoiceNo = $invoiceNo !== '' ? $invoiceNo : null;
        $invoiceDate = $request->filled('invoice_date') ? $request->input('invoice_date') : null;

        if ($iarId > 0 && !$editingIar) {
            return [
                'data' => null,
                'message' => 'IAR report not found.',
                'info' => 'The selected IAR report does not belong to this Purchase Order.',
                'status' => false,
            ];
        }

        if ($editingIar && !$this->isEditableIarStatus($editingIar->status?->name)) {
            return [
                'data' => null,
                'message' => 'IAR report can no longer be edited.',
                'info' => 'Only generated IAR reports can still be edited.',
                'status' => false,
            ];
        }

        if ($invoiceDate && !strtotime($invoiceDate)) {
            return [
                'data' => null,
                'message' => 'Invalid invoice date.',
                'info' => 'Please enter a valid invoice date before saving the IAR report.',
                'errors' => [
                    'invoice_date' => 'Please enter a valid invoice date.',
                ],
                'status' => false,
            ];
        }

        $availableItems = $po->noa->items->keyBy(fn ($item) => (int) $item->id);
        $existingDeliveries = $this->aggregateDeliveredItems($po, $editingIar?->id)->keyBy('item_id');
        $requestedDeliveries = collect($request->input('delivered_items', []))->values();

        if ($requestedDeliveries->isEmpty()) {
            $requestedDeliveries = collect($request->input('selected_item_ids', []))
                ->values()
                ->map(fn ($itemId) => ['item_id' => (int) $itemId]);
        }

        if ($requestedDeliveries->isEmpty()) {
            return [
                'data' => null,
                'message' => 'No delivered item selected.',
                'info' => 'Please select at least one delivered item for the IAR report.',
                'errors' => [
                    'delivered_items' => 'Please select at least one delivered item for the IAR report.',
                ],
                'status' => false,
            ];
        }

        $validatedDeliveries = [];
        $validationErrors = [];

        foreach ($requestedDeliveries as $index => $delivery) {
            $itemId = (int) data_get($delivery, 'item_id');

            if ($itemId <= 0) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] = 'Invalid delivered item selected.';
                continue;
            }

            $availableItem = $availableItems->get($itemId);

            if (!$availableItem) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'This item does not belong to the selected Purchase Order.';
                continue;
            }

            $orderedQuantity = (float) data_get($availableItem, 'item.item.item_quantity', 0);
            $alreadyDeliveredQuantity = min(
                (float) data_get($existingDeliveries->get($itemId), 'delivered_quantity', 0),
                $orderedQuantity
            );
            $availableQuantity = max($orderedQuantity - $alreadyDeliveredQuantity, 0);

            if ($availableQuantity <= 0) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'This item is already fully delivered.';
                continue;
            }

            $deliveryPayload = is_array($delivery) ? $delivery : (array) $delivery;
            $hasExplicitQuantity = array_key_exists('delivered_quantity', $deliveryPayload);
            $rawDeliveredQuantity = data_get($delivery, 'delivered_quantity', $availableQuantity);

            if ($hasExplicitQuantity && !is_numeric($rawDeliveredQuantity)) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'Delivered quantity must be a valid number.';
                continue;
            }

            $deliveredQuantity = (float) ($hasExplicitQuantity ? $rawDeliveredQuantity : $availableQuantity);

            if ($deliveredQuantity <= 0) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'Delivered quantity must be greater than zero.';
                continue;
            }

            if ($availableQuantity > 0 && $deliveredQuantity > $availableQuantity) {
                $validationErrors["delivered_items.{$index}.delivered_quantity"] =
                    'Delivered quantity must not exceed '
                    . $this->normalizeQuantity($availableQuantity)
                    . '.';
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
                'message' => 'Invalid delivered quantity.',
                'info' => 'Please correct the delivered item quantities before saving the IAR report.',
                'errors' => array_merge(
                    ['delivered_items' => 'Please correct the delivered item quantities before saving the IAR report.'],
                    $validationErrors
                ),
                'status' => false,
            ];
        }

        if (empty($validatedDeliveries)) {
            return [
                'data' => null,
                'message' => 'No delivered item selected.',
                'info' => 'Please select at least one delivered item for the IAR report.',
                'errors' => [
                    'delivered_items' => 'Please select at least one delivered item for the IAR report.',
                ],
                'status' => false,
            ];
        }

        $deliveriesForStorage = collect($validatedDeliveries)
            ->map(fn ($delivery) => [
                'item_id' => (int) data_get($delivery, 'item_id'),
                'delivered_quantity' => $this->normalizeQuantity(
                    (float) data_get($delivery, 'delivered_quantity', 0)
                ),
            ])
            ->values()
            ->all();

        if ($editingIar) {
            $normalizedSelections = ProcurementPoIar::normalizeDeliveredItemsForStorage(
                $deliveriesForStorage,
                $po->noa->items
            );

            $updatePayload = [
                'selected_item_ids' => !empty($normalizedSelections) ? array_values($normalizedSelections) : null,
            ];

            if ($this->supportsIarInvoiceFields()) {
                $updatePayload['invoice_no'] = $invoiceNo;
                $updatePayload['invoice_date'] = $invoiceDate;
            }

            $editingIar->update($updatePayload);

            $iar = $editingIar->fresh('status');
        } else {
            $iar = $this->createIAR($po, $deliveriesForStorage, [
                'invoice_no' => $invoiceNo,
                'invoice_date' => $invoiceDate,
            ]);
        }

        return [
            'data' => [
                'iar_id' => $iar->id,
                'iar_code' => $iar->code,
                'iar_status' => $iar->status?->name,
                'invoice_no' => $iar->invoice_no,
                'invoice_date' => $iar->invoice_date?->toDateString(),
                'selected_item_ids' => collect($deliveriesForStorage)->pluck('item_id')->all(),
                'delivered_items' => $iar->selected_item_ids ?? [],
            ],
            'message' => $editingIar
                ? 'IAR report updated successfully!'
                : 'IAR report generated successfully!',
            'info' => $editingIar
                ? 'You have successfully updated this generated IAR report.'
                : "You've successfully generated a new IAR report for this delivery batch.",
            'status' => 'success',
        ];
    }

    public function updateIARStatus($id, $request)
    {
        $po = ProcurementNoaPo::with(
            'status',
            'noa.status',
            'noa.procurement_bac.procurement.items',
            'iars.status',
            'iars.inspected_by.profile'
        )->findOrFail($id);

        $iarId = (int) $request->input('iar_id');
        $iar = $po->iars->firstWhere('id', $iarId);

        if (!$iar) {
            return [
                'data' => null,
                'message' => 'IAR report not found.',
                'info' => 'The selected IAR report does not belong to this Purchase Order.',
                'status' => 'warning',
            ];
        }

        if (!$this->canCurrentUserManageIarInspection()) {
            return [
                'data' => [
                    'iar_id' => $iar->id,
                    'iar_status' => $iar->status?->name,
                ],
                'message' => 'IAR inspection permission denied.',
                'info' => 'Only active IAR committee members can mark an IAR report as Inspected/Completed.',
                'status' => 'warning',
            ];
        }

        if ($iar->status?->name === 'Completed') {
            return [
                'data' => [
                    'iar_id' => $iar->id,
                    'iar_status' => $iar->status?->name,
                ],
                'message' => 'IAR report is already completed.',
                'info' => 'No changes were applied to the selected IAR report.',
                'status' => 'warning',
            ];
        }

        $updatePayload = [
            'status_id' => ListStatus::getID('Completed', 'Procurement'),
        ];

        if ($this->supportsIarInspectedByField()) {
            $updatePayload['inspected_by_id'] = Auth::id();
        }

        $iar->update($updatePayload);

        $po->load('iars.status');
        $deliveryMonitoring = $this->buildDeliveryMonitoringData($po);
        $allItemsDelivered = ((int) data_get($deliveryMonitoring, 'summary.needs_delivery_items', 0)) === 0;
        $allIarsCompleted = $po->iars->isNotEmpty() && !$this->hasPendingIars($po);

        $message = 'IAR report updated successfully!';
        $info = 'The selected IAR report has been marked as Inspected/Completed.';

        if ($allIarsCompleted && $allItemsDelivered) {
            $info = 'All generated IAR reports are now Inspected/Completed. You can now update the Purchase Order status to Completed.';
        } elseif ($allIarsCompleted) {
            $info = 'All generated IAR reports are now Inspected/Completed. Deliver the remaining items before completing the Purchase Order.';
        }

        return [
            'data' => [
                'iar_id' => $iar->id,
                'iar_status' => $iar->fresh('status')->status?->name,
                'po_status' => $po->status?->name,
                'all_iars_completed' => $allIarsCompleted,
                'all_items_delivered' => $allItemsDelivered,
                'ready_for_po_completion' => $allIarsCompleted && $allItemsDelivered,
            ],
            'message' => $message,
            'info' => $info,
            'status' => 'success',
        ];
    }

    public function revertIARStatus($id, $request)
    {
        $po = ProcurementNoaPo::with(
            'status',
            'noa.status',
            'noa.procurement_bac.procurement.items',
            'iars.status',
            'iars.inspected_by.profile'
        )->findOrFail($id);

        $iarId = (int) $request->input('iar_id');
        $iar = $po->iars->firstWhere('id', $iarId);

        if (!$iar) {
            return [
                'data' => null,
                'message' => 'IAR report not found.',
                'info' => 'The selected IAR report does not belong to this Purchase Order.',
                'status' => 'warning',
            ];
        }

        if (!$this->canCurrentUserManageIarInspection()) {
            return [
                'data' => [
                    'iar_id' => $iar->id,
                    'iar_status' => $iar->status?->name,
                ],
                'message' => 'IAR revert permission denied.',
                'info' => 'Only active IAR committee members can revert an IAR report.',
                'status' => 'warning',
            ];
        }

        if ($this->normalizePurchaseOrderStatusName($po->status?->name) !== 'Conformed') {
            return [
                'data' => null,
                'message' => 'Purchase Order is not eligible for IAR revert.',
                'info' => 'An Inspected/Completed IAR report can only be reverted while the Purchase Order status is Conformed.',
                'status' => 'warning',
            ];
        }

        if ($iar->status?->name !== 'Completed') {
            return [
                'data' => [
                    'iar_id' => $iar->id,
                    'iar_status' => $iar->status?->name,
                ],
                'message' => 'IAR report is not completed.',
                'info' => 'Only Inspected/Completed IAR reports can be reverted to Generated.',
                'status' => 'warning',
            ];
        }

        $updatePayload = [
            'status_id' => ListStatus::getID('Generated', 'Procurement')
                ?? ListStatus::getID('Pending', 'Procurement'),
        ];

        if ($this->supportsIarInspectedByField()) {
            $updatePayload['inspected_by_id'] = null;
        }

        $iar->update($updatePayload);

        $po->load('iars.status');
        $deliveryMonitoring = $this->buildDeliveryMonitoringData($po);
        $allItemsDelivered = ((int) data_get($deliveryMonitoring, 'summary.needs_delivery_items', 0)) === 0;
        $allIarsCompleted = $po->iars->isNotEmpty() && !$this->hasPendingIars($po);

        return [
            'data' => [
                'iar_id' => $iar->id,
                'iar_status' => $iar->fresh('status')->status?->name,
                'po_status' => $po->status?->name,
                'all_iars_completed' => $allIarsCompleted,
                'all_items_delivered' => $allItemsDelivered,
                'ready_for_po_completion' => $allIarsCompleted && $allItemsDelivered,
            ],
            'message' => 'IAR report reverted successfully!',
            'info' => 'The selected IAR report has been reverted to Generated.',
            'status' => 'success',
        ];
    }

    public function createIAR($po, array $selectedItemIds = [], array $details = [])
    { 
        $po->loadMissing('noa.items.item.item');
        $normalizedSelections = ProcurementPoIar::normalizeDeliveredItemsForStorage(
            $selectedItemIds,
            $po->noa->items
        );

        $createPayload = [
            'procurement_id' => $po->procurement_id,
            'po_id' => $po->id,
            'code' => ProcurementPoIar::generateIARNumber(),
            'selected_item_ids' => !empty($normalizedSelections) ? array_values($normalizedSelections) : null,
            'status_id' => ListStatus::getID('Generated', 'Procurement')
                ?? ListStatus::getID('Pending', 'Procurement'),
        ];

        if ($this->supportsIarInvoiceFields()) {
            $createPayload['invoice_no'] = data_get($details, 'invoice_no');
            $createPayload['invoice_date'] = data_get($details, 'invoice_date');
        }

        return ProcurementPoIar::create($createPayload)->load('status');
    }


    public function createNTP($request, $po , $user)
    { 
        // Loop through each awarded quotation
            $code = ProcurementPoNtp::generateNTPNumber();
            ProcurementPoNtp::create([
                'procurement_id' => $po->procurement_id,
                'code' => $code,
                'po_id' => $po->id,
                'created_by_id' => $user->id,
                'status_id' => ListStatus::getID('Pending','Procurement'), //set status to "Pending"
            ]);
    }


    public function notConformed($id, $request)
    { 
   
        $user = Auth::user();
        $po = ProcurementNoaPo::with('noa.procurement_bac.procurement' , 'status')->findOrFail($id);

        $po->update([
            'conformed_at' => null,
            'status_id' => ListStatus::getID('Not Conformed','Procurement'), // set status to "Not Conformed"
        ]);     

        $po->noa->update([
            'status_id' => ListStatus::getID('PO Not Conformed','Procurement'), // set noa status to "PO Not Conformed"
        ]);
    

        $po->noa->procurement_bac->update([
            'status_id' => ListStatus::getID('Not Conformed','Procurement'), // set bac resolution status to "PO Not Conformed"
        ]);

        // update quotation items in the specific quotation to "Awarded" for re-award
        $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();
        $item_ids = $noa_items->pluck('item.procurement_item_id')->unique();
        ProcurementQuotationItem::where('quotation_id', $po->noa->procurement_quotation_id)
        ->whereIn('procurement_item_id', $item_ids)
        ->where(function($q) {
            $q->where('status_id', ListStatus::getID('Available for Re-award','Procurement'))
              ->orWhere('status_id', ListStatus::getID('Awarded','Procurement'));
        })
        ->update(['status_id' => ListStatus::getID('Awarded','Procurement')]);

        // create comments for reason
        $po->comments()->create([
            'content' => $request->comment,
            'user_id' => $user->id,
        ]);

        $procurement = $po->noa->procurement_bac->procurement;
        $current_pr_status = $po->noa->procurement_bac->procurement->status_id;

       // Determine if rebid or reaward
        $updated_pr_status = $po->noa->procurement_bac->determine_re_award_or_rebid();
        $procurement->update([
            'status_id' => $updated_pr_status,
        ]);

        // Handle re-award/rebid logic if applicable
        if($updated_pr_status == ListStatus::getID('Re-award','Procurement')){
            $procurement->update([
                'reawarded_count' => $procurement->reawarded_count + 1,
            ]);
        }
        else if($updated_pr_status == ListStatus::getID('Rebid','Procurement')){
            $procurement->update([
                'rebidded_count' => $procurement->rebidded_count + 1,
            ]);
        }
        return [
            'data' =>new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status updated successfully!', 
            'info' => "You've successfully updated Purchase Order Status.",
        ];
    }

    protected function normalizeQuantity($quantity)
    {
        $quantity = round((float) $quantity, 4);

        return floor($quantity) == $quantity ? (int) $quantity : $quantity;
    }

    protected function supportsIarInvoiceFields(): bool
    {
        return Schema::hasColumn('procurement_po_iars', 'invoice_no')
            && Schema::hasColumn('procurement_po_iars', 'invoice_date');
    }

    protected function appendIarSummaries(ProcurementNoaPo $po): void
    {
        $noaItems = $po->noa?->items ?? collect();
        $userCanManageIarInspection = $this->canCurrentUserManageIarInspection();
        $normalizedPoStatus = $this->normalizePurchaseOrderStatusName($po->status?->name);

        $summarize = function ($iar) use ($noaItems, $userCanManageIarInspection, $normalizedPoStatus) {
            if (!$iar) {
                return $iar;
            }

            $normalizedDeliveries = $iar->normalizedDeliveredItems($noaItems);
            $statusName = trim((string) $iar->status?->name);

            $iar->setAttribute('selected_items_count', $normalizedDeliveries->count());
            $iar->setAttribute(
                'delivered_quantity_total',
                (float) $normalizedDeliveries->sum(fn ($entry) => (float) data_get($entry, 'delivered_quantity', 0))
            );
            $iar->setAttribute('inspected_by_name', $this->resolveIarInspectorName($iar));
            $iar->setAttribute(
                'can_update_status',
                $userCanManageIarInspection && $this->isEditableIarStatus($statusName)
            );
            $iar->setAttribute(
                'can_revert_status',
                $userCanManageIarInspection
                    && $normalizedPoStatus === 'Conformed'
                    && $statusName === 'Completed'
            );

            return $iar;
        };

        if ($po->relationLoaded('iar') && $po->iar) {
            $summarize($po->iar);
        }

        if ($po->relationLoaded('iars') && $po->iars) {
            $po->setRelation(
                'iars',
                $po->iars->map(fn ($iar) => $summarize($iar))
            );
        }
    }

    protected function buildDeliveryMonitoringData(ProcurementNoaPo $po): array
    {
        $po->loadMissing(
            'status',
            'iar.status',
            'iars.status',
            'noa.items.item.item.item_unit_type'
        );

        $savedDeliveries = $this->aggregateDeliveredItems($po);
        $savedDeliveryMap = $savedDeliveries->keyBy('item_id');
        $expectedDeliveryDate = $po->date_of_delivery?->copy()->startOfDay();
        $actualDeliveryDate = $this->resolveActualDeliveryDate($po);
        $today = now()->startOfDay();

        $items = $po->noa->items->map(function ($noaItem, $index) use (
            $savedDeliveryMap,
            $expectedDeliveryDate,
            $actualDeliveryDate,
            $today
        ) {
            $quotationItem = $noaItem->item;
            $procurementItem = $quotationItem?->item;
            $orderedQuantity = (float) data_get($procurementItem, 'item_quantity', 0);
            $unitCost = (float) ($quotationItem?->bid_price ?? 0);
            $deliveryDetails = $savedDeliveryMap->get((int) $noaItem->id);
            $deliveredQuantity = (float) data_get(
                $deliveryDetails,
                'delivered_quantity',
                0
            );
            $itemActualDeliveryDate = data_get($deliveryDetails, 'latest_delivery_date');
            $deliveredQuantity = min($deliveredQuantity, $orderedQuantity);
            $remainingQuantity = max($orderedQuantity - $deliveredQuantity, 0);

            [$deliveryStatus, $deliveryStatusVariant] = $this->resolveDeliveryStatus(
                $deliveredQuantity,
                $orderedQuantity
            );
            [$timeliness, $timelinessVariant, $timelinessDays, $timelinessDetail] = $this->resolveDeliveryTimeliness(
                $expectedDeliveryDate,
                $itemActualDeliveryDate ?: $actualDeliveryDate,
                $deliveredQuantity,
                $orderedQuantity,
                $today
            );
            $deliveredAmount = round($unitCost * $deliveredQuantity, 2);
            $penaltyDays = $timeliness === 'Late' ? $timelinessDays : 0;
            $penaltyAmount = round(min($deliveredAmount, $deliveredAmount * 0.01 * $penaltyDays), 2);
            $adjustedAmount = round(max($deliveredAmount - $penaltyAmount, 0), 2);

            return [
                'id' => (int) $noaItem->id,
                'row_number' => $index + 1,
                'item_no' => $procurementItem?->item_no,
                'item_name' => $procurementItem?->item_name,
                'description' => $procurementItem?->item_description,
                'ordered_quantity' => $this->normalizeQuantity($orderedQuantity),
                'delivered_quantity' => $this->normalizeQuantity($deliveredQuantity),
                'remaining_quantity' => $this->normalizeQuantity($remainingQuantity),
                'unit_cost' => $unitCost,
                'delivered_amount' => $deliveredAmount,
                'penalty_days' => $penaltyDays,
                'penalty_rate' => $penaltyDays > 0 ? 0.01 : 0,
                'penalty_amount' => $penaltyAmount,
                'adjusted_amount' => $adjustedAmount,
                'unit' => $procurementItem?->item_unit_type?->name_short
                    ?? $procurementItem?->item_unit_type?->name_long
                    ?? '',
                'expected_delivery_date' => $expectedDeliveryDate?->toDateString(),
                'expected_delivery_date_display' => $expectedDeliveryDate?->format('F j, Y'),
                'actual_delivery_date' => $deliveredQuantity > 0 && ($itemActualDeliveryDate ?: $actualDeliveryDate)
                    ? ($itemActualDeliveryDate ?: $actualDeliveryDate)->toDateString()
                    : null,
                'actual_delivery_date_display' => $deliveredQuantity > 0 && ($itemActualDeliveryDate ?: $actualDeliveryDate)
                    ? ($itemActualDeliveryDate ?: $actualDeliveryDate)->format('F j, Y')
                    : null,
                'delivery_status' => $deliveryStatus,
                'delivery_status_variant' => $deliveryStatusVariant,
                'timeliness' => $timeliness,
                'timeliness_variant' => $timelinessVariant,
                'timeliness_days' => $timelinessDays,
                'timeliness_detail' => $timelinessDetail,
                'needs_delivery' => $remainingQuantity > 0,
            ];
        })->values();

        return [
            'actual_delivery_date' => $actualDeliveryDate?->toDateString(),
            'actual_delivery_date_display' => $actualDeliveryDate?->format('F j, Y'),
            'items' => $items,
            'summary' => [
                'total_items' => $items->count(),
                'delivered_items' => $items->where('delivery_status', 'Delivered')->count(),
                'partial_items' => $items->where('delivery_status', 'Partially Delivered')->count(),
                'pending_items' => $items->where('delivery_status', 'Not Yet Delivered')->count(),
                'needs_delivery_items' => $items->where('needs_delivery', true)->count(),
                'overdue_items' => $items->where('timeliness', 'Overdue')->count(),
                'late_items' => $items->where('timeliness', 'Late')->count(),
                'delivered_amount_total' => round((float) $items->sum('delivered_amount'), 2),
                'penalty_amount_total' => round((float) $items->sum('penalty_amount'), 2),
                'adjusted_amount_total' => round((float) $items->sum('adjusted_amount'), 2),
            ],
        ];
    }

    protected function resolveActualDeliveryDate(ProcurementNoaPo $po)
    {
        if ($po->actual_delivery_date) {
            return $po->actual_delivery_date->copy()->startOfDay();
        }

        $po->loadMissing('iars');
        $latestIar = $po->iars
            ->filter(fn ($iar) => $iar->created_at)
            ->sortByDesc(fn ($iar) => $iar->created_at?->timestamp ?? 0)
            ->first();

        if ($latestIar?->created_at) {
            return $latestIar->created_at->copy()->startOfDay();
        }

        return null;
    }

    protected function aggregateDeliveredItems(ProcurementNoaPo $po, ?int $excludedIarId = null)
    {
        $po->loadMissing('iars.status', 'noa.items.item.item.item_unit_type');

        return $po->iars
            ->when($excludedIarId, fn ($iars) => $iars->where('id', '!=', $excludedIarId))
            ->flatMap(function ($iar) use ($po) {
                $deliveryDate = $iar->created_at?->copy()->startOfDay();

                return $iar->normalizedDeliveredItems($po->noa->items)->map(function ($entry) use ($iar, $deliveryDate) {
                    return [
                        'iar_id' => $iar->id,
                        'item_id' => (int) data_get($entry, 'item_id'),
                        'delivered_quantity' => (float) data_get($entry, 'delivered_quantity', 0),
                        'latest_delivery_date' => $deliveryDate,
                    ];
                });
            })
            ->groupBy('item_id')
            ->map(function ($deliveries, $itemId) {
                $latestDelivery = $deliveries
                    ->filter(fn ($delivery) => data_get($delivery, 'latest_delivery_date'))
                    ->sortByDesc(fn ($delivery) => data_get($delivery, 'latest_delivery_date')?->timestamp ?? 0)
                    ->first();

                return [
                    'item_id' => (int) $itemId,
                    'delivered_quantity' => (float) $deliveries->sum('delivered_quantity'),
                    'latest_delivery_date' => data_get($latestDelivery, 'latest_delivery_date'),
                    'iar_ids' => $deliveries->pluck('iar_id')->unique()->values()->all(),
                ];
            })
            ->values();
    }

    protected function resolveDeliveryStatus(float $deliveredQuantity, float $orderedQuantity): array
    {
        if ($orderedQuantity > 0 && $deliveredQuantity >= $orderedQuantity) {
            return ['Delivered', 'success'];
        }

        if ($deliveredQuantity > 0) {
            return ['Partially Delivered', 'warning'];
        }

        return ['Not Yet Delivered', 'secondary'];
    }

    protected function resolveDeliveryTimeliness(
        $expectedDeliveryDate,
        $actualDeliveryDate,
        float $deliveredQuantity,
        float $orderedQuantity,
        $today
    ): array {
        if ($orderedQuantity > 0 && $deliveredQuantity > 0 && $actualDeliveryDate) {
            if ($expectedDeliveryDate && $actualDeliveryDate->equalTo($expectedDeliveryDate)) {
                return ['Exact Date', 'success', 0, 'Delivered on the expected date'];
            }

            if ($expectedDeliveryDate && $actualDeliveryDate->lessThan($expectedDeliveryDate)) {
                $daysEarly = $actualDeliveryDate->diffInDays($expectedDeliveryDate);

                return ['Early', 'info', 0, 'Delivered ' . $this->formatDayCount($daysEarly) . ' early'];
            }

            if ($expectedDeliveryDate && $actualDeliveryDate->greaterThan($expectedDeliveryDate)) {
                $daysLate = $actualDeliveryDate->diffInDays($expectedDeliveryDate);

                return ['Late', 'danger', $daysLate, 'Late by ' . $this->formatDayCount($daysLate)];
            }

            return ['Delivered', 'success', 0, null];
        }

        if (!$expectedDeliveryDate) {
            return ['Pending', 'secondary', 0, null];
        }

        if ($today->greaterThan($expectedDeliveryDate)) {
            $daysOverdue = $today->diffInDays($expectedDeliveryDate);

            return ['Overdue', 'danger', $daysOverdue, 'Overdue by ' . $this->formatDayCount($daysOverdue)];
        }

        if ($today->equalTo($expectedDeliveryDate)) {
            return ['Due Today', 'warning', 0, 'Expected today'];
        }

        return ['Pending', 'secondary', 0, null];
    }

    protected function formatDayCount(int $days): string
    {
        return $days . ' day' . ($days === 1 ? '' : 's');
    }

    protected function normalizePurchaseOrderStatusName(?string $statusName): ?string
    {
        $normalized = trim((string) $statusName);

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'PO Conformed',
            'Partially Conformed',
            'PO Partially Conformed' => 'Conformed',
            'Items Delivered',
            'PO Items Delivered',
            'Partially Items Delivered',
            'PO Items Delivered',
            'PO Partially Items Delivered',
            'Items Partially Delivered',
            'PO Items Partially Delivered' => 'Items Delivered',
            default => $normalized,
        };
    }

    protected function hasPendingIars(ProcurementNoaPo $po): bool
    {
        $po->loadMissing('iars.status');

        return $po->iars->contains(fn ($iar) => $iar->status?->name !== 'Completed');
    }

    protected function supportsIarInspectedByField(): bool
    {
        return Schema::hasColumn('procurement_po_iars', 'inspected_by_id');
    }

    protected function canCurrentUserManageIarInspection(): bool
    {
        $userId = Auth::id();

        if (!$userId) {
            return false;
        }

        $designationIds = array_values(array_filter([
            ListDropdown::getID('IAR Chairperson', 'Designation'),
            ListDropdown::getID('IAR Member', 'Designation'),
        ]));

        if (empty($designationIds)) {
            return false;
        }

        return OrgChart::query()
            ->where('is_active', 1)
            ->whereIn('designation_id', $designationIds)
            ->where(function ($query) use ($userId) {
                $query->where(function ($subQuery) use ($userId) {
                    $subQuery->where('is_oic', 1)
                        ->where('oic_id', $userId);
                })->orWhere(function ($subQuery) use ($userId) {
                    $subQuery->where(function ($flagQuery) {
                        $flagQuery->whereNull('is_oic')
                            ->orWhere('is_oic', 0);
                    })->where('user_id', $userId);
                });
            })
            ->exists();
    }

    protected function resolveIarInspectorName($iar): ?string
    {
        return data_get($iar, 'inspected_by.profile.fullname')
            ?: data_get($iar, 'inspected_by.profile.full_name')
            ?: data_get($iar, 'inspected_by.username');
    }

    protected function isEditableIarStatus(?string $statusName): bool
    {
        return in_array(trim((string) $statusName), ['Generated', 'Pending'], true);
    }

    public function revertStatus($id, $request)
    {
        $po = ProcurementNoaPo::with('noa.procurement_bac.procurement', 'status', 'noa.status')->findOrFail($id);
        $procurement = $po->noa->procurement_bac->procurement;
        $current_pr_status = $procurement->status_id;

        $currentStatusName = $this->normalizePurchaseOrderStatusName($po->status?->name);
        $revertedPoStatus = null;
        $revertedNoaStatus = null;

        if ($currentStatusName === 'Completed') {
            // rollback previously posted inventory quantities before reverting status
            $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();
            $item_ids = $noa_items->pluck('item.procurement_item_id')->unique()->filter()->values();
            $this->rollbackCompletedItemsFromInventory($po, $item_ids);

            $revertedPoStatus = 'Items Delivered';
            $revertedNoaStatus = 'PO Items Delivered';
        } elseif ($currentStatusName === 'Items Delivered') {
            $revertedPoStatus = 'Conformed';
            $revertedNoaStatus = 'PO Conformed';
        } elseif ($currentStatusName === 'Conformed') {
            $revertedPoStatus = 'Issued';
            $revertedNoaStatus = 'PO Issued';
        } elseif ($currentStatusName === 'Issued') {
            $revertedPoStatus = 'Created';
            $revertedNoaStatus = 'PO Created';
        }

        if (!$revertedPoStatus || !$revertedNoaStatus) {
            return [
                'data' => new ProcurementNoaPoResource($po),
                'message' => 'PO status cannot be reverted from the current step.',
                'info' => 'Nothing changed.',
                'status' => 'warning',
            ];
        }

        $revertPoData = [
            'status_id' => ListStatus::getID($revertedPoStatus, 'Procurement'),
        ];

        if ($this->supportsActualDeliveryDateColumn() && $revertedPoStatus === 'Conformed') {
            $revertPoData['actual_delivery_date'] = null;
        } elseif ($this->supportsActualDeliveryDateColumn() && $revertedPoStatus === 'Issued') {
            $revertPoData['actual_delivery_date'] = null;
            $revertPoData['conformed_at'] = null;
        } elseif ($this->supportsActualDeliveryDateColumn() && $revertedPoStatus === 'Created') {
            $revertPoData['actual_delivery_date'] = null;
            $revertPoData['released_at'] = null;
            $revertPoData['conformed_at'] = null;
        } elseif ($revertedPoStatus === 'Issued') {
            $revertPoData['conformed_at'] = null;
        } elseif ($revertedPoStatus === 'Created') {
            $revertPoData['released_at'] = null;
            $revertPoData['conformed_at'] = null;
        }

        $po->update($revertPoData);

        $po->noa->update([
            'status_id' => ListStatus::getID($revertedNoaStatus, 'Procurement'),
        ]);

        $po->refresh();
        $po->noa->refresh();

        if (
            $current_pr_status == ListStatus::getID('Re-award', 'Procurement') ||
            $current_pr_status == ListStatus::getID('Rebid', 'Procurement')
        ) {
            $updated_pr_substatus = $po->noa->procurement_bac->overall_substatus($current_pr_status);
            $procurement->update([
                'sub_status_id' => $updated_pr_substatus,
            ]);
        } else {
            $updated_pr_status = $po->noa->procurement_bac->overall_status($current_pr_status);
            $procurement->update([
                'status_id' => $updated_pr_status,
                'sub_status_id' => null,
            ]);
        }

        return [
            'data' => new ProcurementNoaPoResource($po),
            'message' => 'Purchase Order Status reverted successfully!',
            'info' => "You've successfully reverted Purchase Order Status.",
            'status' => 'success',
        ];
    }

    protected function finalizeCompletedPurchaseOrder(ProcurementNoaPo $po, Procurement $procurement, $current_pr_status): void
    {
        $po->update([
            'status_id' => ListStatus::getID('Completed', 'Procurement'),
        ]);

        $po->noa->update([
            'status_id' => ListStatus::getID('Completed', 'Procurement'),
        ]);

        $noa_items = ProcurementBacNoaItem::where('procurement_bac_noa_id', $po->noa->id)->get();

        foreach ($noa_items as $noa_item) {
            $quotation_item = $noa_item->item;

            if ($quotation_item) {
                $quotation_item->update([
                    'status_id' => ListStatus::getID('Completed', 'Procurement'),
                ]);
            }
        }

        $item_ids = $noa_items->pluck('item.procurement_item_id')->unique();

        ProcurementItem::whereIn('id', $item_ids)->update([
            'status_id' => ListStatus::getID('Completed', 'Procurement'),
        ]);

        $this->syncCompletedItemsToInventory($po, $item_ids);

        ProcurementQuotationItem::whereHas('quotation', function ($q) use ($po) {
            $q->where('procurement_id', $po->procurement_id);
        })->whereIn('procurement_item_id', $item_ids)
            ->where(function ($q) {
                $q->where('status_id', ListStatus::getID('Available for Re-award', 'Procurement'))
                    ->orWhere('status_id', ListStatus::getID('Awarded', 'Procurement'));
            })
            ->update([
                'status_id' => ListStatus::getID('Not Awarded', 'Procurement'),
            ]);

        $procurement->refresh();

        if ($procurement->items->every(fn ($item) => $item->status_id == ListStatus::getID('Completed', 'Procurement'))) {
            if ($current_pr_status == ListStatus::getID('Re-award', 'Procurement') || $current_pr_status == ListStatus::getID('Rebid', 'Procurement')) {
                $procurement->update([
                    'status_id' => ListStatus::getID('Completed', 'Procurement'),
                    'sub_status_id' => ListStatus::getID('Completed', 'Procurement'),
                ]);
            } else {
                $procurement->update([
                    'status_id' => ListStatus::getID('Completed', 'Procurement'),
                    'sub_status_id' => null,
                ]);
            }
        } else {
            if ($current_pr_status == ListStatus::getID('Re-award', 'Procurement') || $current_pr_status == ListStatus::getID('Rebid', 'Procurement')) {
                $procurement->update([
                    'status_id' => ListStatus::getID('Partially Completed', 'Procurement'),
                    'sub_status_id' => ListStatus::getID('Partially Completed', 'Procurement'),
                ]);
            } else {
                $procurement->update([
                    'status_id' => ListStatus::getID('Partially Completed', 'Procurement'),
                    'sub_status_id' => null,
                ]);
            }
        }
    }


    private function syncCompletedItemsToInventory(ProcurementNoaPo $po, $procurementItemIds): void
    {
        $itemIds = collect($procurementItemIds)->filter()->unique()->values();
        if ($itemIds->isEmpty()) {
            return;
        }

        $categoryId = $this->defaultInventoryCategoryId();
        if (!$categoryId) {
            Log::warning('Inventory sync skipped: missing Inventory Category dropdown.', [
                'po_id' => $po->id,
                'procurement_id' => $po->procurement_id,
            ]);
            return;
        }

        $items = ProcurementItem::with('item_unit_type')
            ->whereIn('id', $itemIds)
            ->get();

        foreach ($items as $item) {
            $unitId = $this->resolveInventoryUnitIdFromProcurementItem($item);
            if (!$unitId) {
                Log::warning('Inventory sync skipped item: unit mapping not found.', [
                    'po_id' => $po->id,
                    'procurement_item_id' => $item->id,
                    'item_unit_type_id' => $item->item_unit_type_id,
                ]);
                continue;
            }

            $itemName = trim((string) $item->item_description);
            if ($itemName === '') {
                $itemName = 'Procurement Item #' . $item->id;
            }

            $inventory = Inventory::firstOrCreate(
                [
                    'name' => $itemName,
                    'unit_id' => $unitId,
                ],
                [
                    'description' => $item->item_description,
                    'category_id' => $categoryId,
                    'min_stock_level' => 0,
                ]
            );

            $stock = InventoryStock::firstOrNew([
                'inventory_id' => $inventory->id,
                'location_id' => $po->place_of_delivery_id,
            ]);

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $incomingQuantity = (float) ($item->item_quantity ?? 0);
            $newQuantity = $currentQuantity + $incomingQuantity;

            $stock->quantity = $newQuantity;
            $stock->status = $this->resolveInventoryStockStatus($newQuantity, (float) $inventory->min_stock_level);
            $stock->last_updated = now();
            $stock->save();
        }
    }

    private function rollbackCompletedItemsFromInventory(ProcurementNoaPo $po, $procurementItemIds): void
    {
        $itemIds = collect($procurementItemIds)->filter()->unique()->values();
        if ($itemIds->isEmpty()) {
            return;
        }

        $items = ProcurementItem::with('item_unit_type')
            ->whereIn('id', $itemIds)
            ->get();

        foreach ($items as $item) {
            $unitId = $this->resolveInventoryUnitIdFromProcurementItem($item);
            if (!$unitId) {
                continue;
            }

            $itemName = trim((string) $item->item_description);
            if ($itemName === '') {
                $itemName = 'Procurement Item #' . $item->id;
            }

            $inventory = Inventory::where('name', $itemName)
                ->where('unit_id', $unitId)
                ->first();

            if (!$inventory) {
                continue;
            }

            $stock = InventoryStock::where('inventory_id', $inventory->id)
                ->where('location_id', $po->place_of_delivery_id)
                ->first();

            if (!$stock) {
                continue;
            }

            $currentQuantity = (float) ($stock->quantity ?? 0);
            $outgoingQuantity = (float) ($item->item_quantity ?? 0);
            $newQuantity = max($currentQuantity - $outgoingQuantity, 0);

            $stock->quantity = $newQuantity;
            $stock->status = $this->resolveInventoryStockStatus($newQuantity, (float) $inventory->min_stock_level);
            $stock->last_updated = now();
            $stock->save();
        }
    }

    private function resolveInventoryUnitIdFromProcurementItem(ProcurementItem $item): ?int
    {
        $unitType = $item->item_unit_type;
        if (!$unitType) {
            return null;
        }

        $candidates = collect([
            $unitType->name_short ?? null,
            $unitType->name_long ?? null,
        ])->filter()->map(fn($name) => trim((string) $name))->filter()->values();

        foreach ($candidates as $candidate) {
            $unit = ListDropdown::where('classification', 'Unit')
                ->where(function ($query) use ($candidate) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($candidate)])
                        ->orWhereRaw('LOWER(others) = ?', [strtolower($candidate)]);
                })
                ->first();

            if ($unit) {
                return (int) $unit->id;
            }
        }

        return null;
    }

    private function defaultInventoryCategoryId(): ?int
    {
        return ListDropdown::where('classification', 'Inventory Category')
            ->orderBy('id')
            ->value('id');
    }

    private function resolveInventoryStockStatus(float $quantity, float $minStockLevel): string
    {
        if ($quantity <= 0) {
            return 'out';
        }

        if ($quantity <= $minStockLevel) {
            return 'low';
        }

        return 'available';
    }

    protected function supportsActualDeliveryDateColumn(): bool
    {
        return Schema::hasColumn('procurement_noa_pos', 'actual_delivery_date');
    }


   

}
