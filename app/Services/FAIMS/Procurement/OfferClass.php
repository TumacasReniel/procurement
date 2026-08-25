<?php

namespace App\Services\FAIMS\Procurement;
use App\Models\Procurement;
use App\Models\ListStatus;
use App\Models\ProcurementQuotationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OfferClass
{
    public function __construct(protected ProcurementGate $gate)
    {
    }

    public function save($request){
        $this->gate->authorize(ProcurementGate::MANAGE_RFQ, 'bid_price');

        $item = ProcurementQuotationItem::with('quotation')->findOrFail($request->id);

        // A bid can only be encoded while its RFQ is still open for bidding.
        $this->ensureQuotationAcceptsBids($item);

        $isFree = $request->boolean('is_free');
        $isNoOffer = !$isFree && $request->boolean('is_no_offer');
        $isNotApplicable = !$isFree && !$isNoOffer && $request->boolean('is_not_applicable');

        if (!$isFree && !$isNoOffer && !$isNotApplicable && (float) $request->bid_price < 0) {
            throw ValidationException::withMessages([
                'bid_price' => 'Bid price cannot be negative.',
            ]);
        }

        // update bid offer for bid_item
        $item->bid_price = $isFree ? 0 : ($isNoOffer || $isNotApplicable ? null : $request->bid_price);
        if (Schema::hasColumn('procurement_quotation_items', 'is_free')) {
            $item->is_free = $isFree;
        }
        if (Schema::hasColumn('procurement_quotation_items', 'is_no_offer')) {
            $item->is_no_offer = $isNoOffer;
        }
        if (Schema::hasColumn('procurement_quotation_items', 'is_not_applicable')) {
            $item->is_not_applicable = $isNotApplicable;
        }
        $item->technical_proposal = ($isNoOffer || $isNotApplicable)
            ? null
            : $request->technical_proposal;
        $item->save();

        $item->quotation->update([
            'delivery_term' => $request->delivery_term,
        ]);

        return [
            'data' => $item,
            'message' => 'Bid Offer updated successfuly!',
            'info' => "You've successfully updated the Bid Offer.",
        ];
    }


    public function save_bid_for_award($request){
        $this->gate->authorize(ProcurementGate::EVALUATE_BIDS, 'items');

        $procurement = Procurement::with('status')->findOrFail($request->procurement_id);
        $awardedStatusId = ListStatus::getID('Awarded', 'Procurement');

        $awardedItemIds = collect($request->items)->pluck('id')->filter()->values();

        // Load the items being awarded and verify every one of them belongs to THIS
        // procurement — the request only sends quotation item ids, so without this a
        // caller could award items on someone else's PR.
        $awardedItems = ProcurementQuotationItem::with(['item', 'quotation'])
            ->whereIn('id', $awardedItemIds)
            ->get();

        $this->ensureItemsBelongToProcurement($awardedItems, $procurement, $awardedItemIds);

        // An item must not be awarded to two suppliers at once.
        $this->ensureNoDuplicateAwardPerItem($awardedItems);

        foreach ($awardedItems as $item) {
            // A bid must actually exist and be within the approved unit cost (ABC).
            $this->ensureItemIsAwardable($item);

            $item->status_id = $awardedStatusId;
            $item->save();
        }

        $notAwardedIds = collect($request->itemsNotAvailableForAward)->pluck('id')->filter()->values();

        $notAwardedItems = ProcurementQuotationItem::with(['item', 'quotation'])
            ->whereIn('id', $notAwardedIds)
            ->get();

        $this->ensureItemsBelongToProcurement($notAwardedItems, $procurement, $notAwardedIds);

        foreach ($notAwardedItems as $item) {
            $bidPrice = (float) $item->bid_price;
            $unitCost = (float) optional($item->item)->item_unit_cost;
            $isWithinUnitCost = $unitCost <= 0 || $bidPrice <= $unitCost;

            $item->status_id = ($item->is_free || ($bidPrice > 0 && $isWithinUnitCost))
                // still a viable fallback bid if the awarded supplier defaults
                ? ListStatus::getID('Available for Re-award', 'Procurement')
                : ListStatus::getID('Not Available for Award/Re-award', 'Procurement');

            $item->save();
        }

        if($procurement && $procurement->status->name === 'Rebid'){
            // update PR status to "For BAC Resolution"
            $procurement->update([
                'sub_status_id' => ListStatus::getID('For BAC Resolution', 'Procurement'),
            ]);
        }
        else{
             // update PR status to "For BAC Resolution"
            $procurement->update([
                'status_id' => ListStatus::getID('For BAC Resolution', 'Procurement'),
            ]);
        }

        return [
            'data' => $request->items,
            'message' => 'Bid Items awarded successfuly!',
            'info' => "You've successfully awarded the Bid Items.",
        ];
    }

    /**
     * Bids may only be encoded/edited while the RFQ is open — not after the BAC has
     * begun evaluating, and not past the submission deadline.
     */
    protected function ensureQuotationAcceptsBids(ProcurementQuotationItem $item): void
    {
        $quotation = $item->quotation;

        if (! $quotation) {
            throw ValidationException::withMessages([
                'bid_price' => 'This bid item is not linked to a Request for Quotation.',
            ]);
        }

        $lockedStatuses = ['Failed RFQ', 'Completed', 'Cancelled'];

        if (in_array($quotation->status?->name, $lockedStatuses, true)) {
            throw ValidationException::withMessages([
                'bid_price' => 'This Request for Quotation is closed and no longer accepts bids.',
            ]);
        }

        $deadline = $quotation->submission_not_later_than;

        if ($deadline && now()->startOfDay()->gt(\Illuminate\Support\Carbon::parse($deadline)->endOfDay())) {
            throw ValidationException::withMessages([
                'bid_price' => 'The submission deadline for this Request for Quotation has passed.',
            ]);
        }
    }

    protected function ensureItemsBelongToProcurement($items, Procurement $procurement, $requestedIds): void
    {
        if ($items->count() !== collect($requestedIds)->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected bid items could not be found.',
            ]);
        }

        $foreign = $items->first(
            fn ($item) => (int) $item->quotation?->procurement_id !== (int) $procurement->id
        );

        if ($foreign) {
            throw ValidationException::withMessages([
                'items' => 'One or more selected bid items do not belong to this purchase request.',
            ]);
        }
    }

    /**
     * The same PR line item must not be awarded to more than one supplier.
     */
    protected function ensureNoDuplicateAwardPerItem($awardedItems): void
    {
        $duplicated = $awardedItems
            ->groupBy('procurement_item_id')
            ->filter(fn ($group) => $group->count() > 1);

        if ($duplicated->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'The same item cannot be awarded to more than one supplier.',
            ]);
        }
    }

    /**
     * An awarded item needs a real bid: present, non-negative, and within the
     * approved unit cost (ABC) unless it was offered for free.
     */
    protected function ensureItemIsAwardable(ProcurementQuotationItem $item): void
    {
        $name = optional($item->item)->item_name ?: "item #{$item->procurement_item_id}";

        if ($item->is_no_offer || $item->is_not_applicable) {
            throw ValidationException::withMessages([
                'items' => "Cannot award {$name}: the supplier did not offer this item.",
            ]);
        }

        if ($item->is_free) {
            return;
        }

        if ($item->bid_price === null || $item->bid_price === '') {
            throw ValidationException::withMessages([
                'items' => "Cannot award {$name}: no bid price was submitted.",
            ]);
        }

        $bidPrice = (float) $item->bid_price;

        if ($bidPrice <= 0) {
            throw ValidationException::withMessages([
                'items' => "Cannot award {$name}: the bid price must be greater than zero.",
            ]);
        }

        $unitCost = (float) optional($item->item)->item_unit_cost;

        if ($unitCost > 0 && $bidPrice > $unitCost) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Cannot award %s: the bid of PHP %s exceeds the approved unit cost (ABC) of PHP %s.',
                    $name,
                    number_format($bidPrice, 2),
                    number_format($unitCost, 2)
                ),
            ]);
        }
    }
}
