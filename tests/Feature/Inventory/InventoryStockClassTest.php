<?php

use App\Models\InventoryItem;
use App\Models\InventoryRis;
use App\Models\InventoryRisItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockAdjustment;
use App\Models\InventoryStockDrain;
use App\Models\InventoryWithdrawal;
use App\Models\ListStatus;
use App\Models\UnitType;
use App\Services\Inventory\InventoryStockClass;
use Illuminate\Http\Request;

/*
| The quantity math for FIFO consumption, void/restore, partial issuance, and stock
| adjustments all lives in InventoryStockClass. These tests pin down the two bugs fixed
| in this session: void/restore was putting quantity back on whichever stock batch had
| the highest quantity (not the batch(es) actually drained), and a "decrease" adjustment
| larger than on-hand stock was silently clamped instead of rejected.
*/

function inventoryStockService(): InventoryStockClass
{
    return app(InventoryStockClass::class);
}

function inventoryStatus(string $name): int
{
    return ListStatus::firstOrCreate(
        ['name' => $name, 'classification' => 'Inventory'],
        ['type' => 'n/a', 'color' => 'n/a', 'bg' => 'n/a', 'icon' => 'n/a', 'is_active' => 1]
    )->id;
}

beforeEach(function () {
    // voidRis()/voidWithdrawal() both require a "Cancelled" status to exist, even
    // though individual tests only reference the statuses they directly assign.
    foreach (['Pending', 'Completed', 'Cancelled', 'Approved'] as $name) {
        inventoryStatus($name);
    }
});

function inventoryUnitTypeId(): int
{
    return UnitType::firstOrCreate(
        ['name_short' => 'pc'],
        ['name_long' => 'Piece']
    )->id;
}

function makeInventoryItem(string $name): InventoryItem
{
    return InventoryItem::create(['name' => $name]);
}

function makeInventoryStock(InventoryItem $item, float $quantity, float $unitCost): InventoryStock
{
    return InventoryStock::create([
        'item_id'   => $item->id,
        'quantity'  => $quantity,
        'unit_id'   => inventoryUnitTypeId(),
        'unit_cost' => $unitCost,
    ]);
}

it('drains stock oldest batch first and records a ledger row per batch touched', function () {
    $item = makeInventoryItem('FIFO Drain Item');
    $older = makeInventoryStock($item, 5, 10);   // created first -> lower id -> drained first
    $newer = makeInventoryStock($item, 10, 20);

    $value = inventoryStockService()->drainStock($item->id, 8, 'test_source', 1);

    expect($older->fresh()->quantity)->toEqual('0.00')
        ->and($newer->fresh()->quantity)->toEqual('7.00')
        // 5 units at 10 + 3 units at 20 = 50 + 60 = 110
        ->and($value)->toBe(110.0);

    $drains = InventoryStockDrain::where('source_type', 'test_source')->where('source_id', 1)->orderBy('id')->get();
    expect($drains)->toHaveCount(2)
        ->and((float) $drains[0]->quantity)->toBe(5.0)
        ->and($drains[0]->inventory_stock_id)->toBe($older->id)
        ->and((float) $drains[1]->quantity)->toBe(3.0)
        ->and($drains[1]->inventory_stock_id)->toBe($newer->id);
});

it('throws when draining more than is on hand', function () {
    $item = makeInventoryItem('Insufficient Stock Item');
    makeInventoryStock($item, 2, 10);

    inventoryStockService()->drainStock($item->id, 5, 'test_source', 2);
})->throws(Exception::class, 'Insufficient stock');

it('voids a completed RIS by restoring quantity to the exact batches it was drained from, not the highest-quantity batch', function () {
    $item = makeInventoryItem('RIS Void Item');
    $older = makeInventoryStock($item, 5, 10);
    $newer = makeInventoryStock($item, 10, 20);

    $ris = InventoryRis::create([
        'ris_date'  => now()->toDateString(),
        'status_id' => inventoryStatus('Pending'),
    ]);
    $risItem = InventoryRisItem::create([
        'ris_id'             => $ris->id,
        'item_id'            => $item->id,
        'quantity_requested' => 8,
        'quantity_issued'    => 8,
    ]);

    inventoryStockService()->deductStockForRis($ris->fresh(['items']));

    // Drained 5 from $older (now 0) and 3 from $newer (now 7) — if void restored to
    // "highest quantity" it would dump all 8 onto $newer instead of the real split.
    expect($older->fresh()->quantity)->toEqual('0.00')
        ->and($newer->fresh()->quantity)->toEqual('7.00');

    $ris->update(['status_id' => inventoryStatus('Completed')]);

    inventoryStockService()->voidRis($ris->fresh(['items']));

    expect($older->fresh()->quantity)->toEqual('5.00')
        ->and($newer->fresh()->quantity)->toEqual('10.00')
        ->and(InventoryStockDrain::where('source_type', 'ris_item')->where('source_id', $risItem->id)->count())->toBe(0);
});

it('voids a completed withdrawal by restoring quantity to the exact batches it was drained from', function () {
    $item = makeInventoryItem('Withdrawal Void Item');
    $older = makeInventoryStock($item, 4, 10);
    $newer = makeInventoryStock($item, 10, 20);

    $withdrawal = InventoryWithdrawal::create([
        'inventory_id' => $item->id,
        'quantity'     => 6,
        'status_id'    => inventoryStatus('Pending'),
    ]);

    inventoryStockService()->deductStockForWithdrawal($withdrawal->fresh());

    // 4 from $older (now 0) + 2 from $newer (now 8).
    expect($older->fresh()->quantity)->toEqual('0.00')
        ->and($newer->fresh()->quantity)->toEqual('8.00');

    $withdrawal->update(['status_id' => inventoryStatus('Completed')]);

    inventoryStockService()->voidWithdrawal($withdrawal->fresh());

    expect($older->fresh()->quantity)->toEqual('4.00')
        ->and($newer->fresh()->quantity)->toEqual('10.00');
});

it('accumulates partial withdrawal issuances and completes once the full quantity is issued', function () {
    $item = makeInventoryItem('Partial Issue Item');
    makeInventoryStock($item, 20, 10);

    $withdrawal = InventoryWithdrawal::create([
        'inventory_id' => $item->id,
        'quantity'     => 10,
        'status_id'    => inventoryStatus('Pending'),
    ]);

    $service = inventoryStockService();

    $service->partialIssueWithdrawal(new Request(['issued_quantity' => 4]), $withdrawal->fresh());
    $withdrawal->refresh();
    expect((float) $withdrawal->issued_quantity)->toBe(4.0)
        ->and((int) $withdrawal->status_id)->not->toBe(inventoryStatus('Completed'));

    $service->partialIssueWithdrawal(new Request(['issued_quantity' => 6]), $withdrawal->fresh());
    $withdrawal->refresh();

    expect((float) $withdrawal->issued_quantity)->toBe(10.0)
        ->and((int) $withdrawal->status_id)->toBe(inventoryStatus('Completed'))
        // weighted average unit cost across both partial issuances, all drawn at unit_cost 10
        ->and((float) $withdrawal->unit_cost)->toBe(10.0);
});

it('rejects a stock adjustment decrease larger than what is on hand instead of clamping it', function () {
    $service = inventoryStockService();
    $reflection = new ReflectionMethod($service, 'computeAdjustedQuantity');
    $reflection->setAccessible(true);

    expect(fn () => $reflection->invoke($service, 'decrease', 10.0, 15.0))
        ->toThrow(Exception::class, 'Cannot decrease by 15: only 10 on hand.');

    expect($reflection->invoke($service, 'decrease', 10.0, 4.0))->toBe(6.0);
});
