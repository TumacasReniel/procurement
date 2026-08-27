<?php

use App\Http\Controllers\VelzonRoutesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::domain('attendance.' . config('app.app_host'))->as('attendance.')->group(function () {
    Route::get('/', [App\Http\Controllers\Public\AttendanceController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Public\AttendanceController::class, 'store']);
    Route::post('/recognize', [App\Http\Controllers\Public\AttendanceController::class, 'recognize']);
    Route::get('/{station}', [App\Http\Controllers\Public\AttendanceController::class, 'show'])
    ->middleware('attendance') // Middleware to restrict access
    ->name('attendance.station');
});

Route::get('/search', [App\Http\Controllers\SearchController::class, 'search']);
Route::get('/dropdowns', [App\Http\Controllers\SearchController::class, 'dropdowns']);
Route::get('/attendance', [App\Http\Controllers\Public\AttendanceController::class, 'index']);
Route::post('/attendance', [App\Http\Controllers\Public\AttendanceController::class, 'store']);
Route::post('/recognize', [App\Http\Controllers\Public\AttendanceController::class, 'recognize']);

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.home');
    
    Route::resource('/dtr', App\Http\Controllers\Portal\DtrController::class);
    Route::resource('/requests', App\Http\Controllers\Portal\RequestController::class);
    Route::get('/inventory-dashboard', [App\Http\Controllers\Inventory\DashboardController::class, 'index'])->name('inventory.dashboard');
    Route::resource('/inventory-stocks', App\Http\Controllers\Inventory\InventoryStockController::class)->only(['index','store','update']);
    Route::resource('/inventory-items', App\Http\Controllers\Inventory\InventoryItemController::class)->only(['index','store','update']);
    Route::resource('/inventory-categories', App\Http\Controllers\Inventory\InventoryCategoryController::class)->only(['index','store','update']);
    Route::resource('/inventory-reports', App\Http\Controllers\Inventory\InventoryReportController::class)->only(['index','show','store','update']);
    Route::resource('/inventory-properties', App\Http\Controllers\Inventory\InventoryItemPropertyController::class)
        ->parameters(['inventory-properties' => 'inventory_item_property'])
        ->only(['index','store','update']);
    Route::resource('/inventory-receivings', App\Http\Controllers\Inventory\InventoryReceivingController::class)->only(['index','store','update']);
    Route::get('/inventory-procurement-receivings', [App\Http\Controllers\Inventory\InventoryReceivingController::class, 'procurementReceivings'])->name('inventory.procurement-receivings');
    Route::resource('/inventory-withdrawals', App\Http\Controllers\Inventory\InventoryWithdrawalController::class)->only(['index','store','update']);
    Route::post('/inventory-withdrawals/{inventory_withdrawal}/void', [App\Http\Controllers\Inventory\InventoryWithdrawalController::class, 'void'])->name('inventory-withdrawals.void');
    Route::post('/inventory-withdrawals/{inventory_withdrawal}/partial-issue', [App\Http\Controllers\Inventory\InventoryWithdrawalController::class, 'partialIssue'])->name('inventory-withdrawals.partial-issue');

    Route::resource('/inventory-ris', App\Http\Controllers\Inventory\InventoryRisController::class)->only(['index','show','store','update']);
    Route::post('/inventory-ris/{inventory_ri}/void', [App\Http\Controllers\Inventory\InventoryRisController::class, 'void'])->name('inventory-ris.void');

    // Stock Adjustments
    Route::resource('/inventory-adjustments', App\Http\Controllers\Inventory\InventoryStockAdjustmentController::class)->only(['index','store','update']);

    // Physical Inventory / Cycle Count
    Route::resource('/inventory-physical-counts', App\Http\Controllers\Inventory\InventoryPhysicalCountController::class)->only(['index','show','store','update']);

    // ICS & PAR
    Route::resource('/inventory-ics', App\Http\Controllers\Inventory\InventoryIcsController::class)->only(['index','show','store','update']);
    Route::resource('/inventory-par', App\Http\Controllers\Inventory\InventoryParController::class)->only(['index','show','store','update']);

    // Inventory destroy actions carry real data-loss risk (voiding/undoing stock movements),
    // so they're restricted beyond plain auth — same role list as AuthorizesInventoryAccess.
    Route::middleware(['role:Administrator,Supply Officer,Supply Staff'])->group(function () {
        Route::delete('/inventory-stocks/{inventory_stock}', [App\Http\Controllers\Inventory\InventoryStockController::class, 'destroy'])->name('inventory-stocks.destroy');
        Route::delete('/inventory-items/{inventory_item}', [App\Http\Controllers\Inventory\InventoryItemController::class, 'destroy'])->name('inventory-items.destroy');
        Route::delete('/inventory-categories/{inventory_category}', [App\Http\Controllers\Inventory\InventoryCategoryController::class, 'destroy'])->name('inventory-categories.destroy');
        Route::delete('/inventory-reports/{inventory_report}', [App\Http\Controllers\Inventory\InventoryReportController::class, 'destroy'])->name('inventory-reports.destroy');
        Route::delete('/inventory-properties/{inventory_item_property}', [App\Http\Controllers\Inventory\InventoryItemPropertyController::class, 'destroy'])->name('inventory-properties.destroy');
        Route::delete('/inventory-receivings/{inventory_receiving}', [App\Http\Controllers\Inventory\InventoryReceivingController::class, 'destroy'])->name('inventory-receivings.destroy');
        Route::delete('/inventory-withdrawals/{inventory_withdrawal}', [App\Http\Controllers\Inventory\InventoryWithdrawalController::class, 'destroy'])->name('inventory-withdrawals.destroy');
        Route::delete('/inventory-ris/{inventory_ri}', [App\Http\Controllers\Inventory\InventoryRisController::class, 'destroy'])->name('inventory-ris.destroy');
        Route::delete('/inventory-adjustments/{inventory_stock_adjustment}', [App\Http\Controllers\Inventory\InventoryStockAdjustmentController::class, 'destroy'])->name('inventory-adjustments.destroy');
        Route::delete('/inventory-physical-counts/{inventory_physical_count}', [App\Http\Controllers\Inventory\InventoryPhysicalCountController::class, 'destroy'])->name('inventory-physical-counts.destroy');
        Route::delete('/inventory-ics/{inventory_ic}', [App\Http\Controllers\Inventory\InventoryIcsController::class, 'destroy'])->name('inventory-ics.destroy');
        Route::delete('/inventory-par/{inventory_par}', [App\Http\Controllers\Inventory\InventoryParController::class, 'destroy'])->name('inventory-par.destroy');
    });

    // Print routes
    Route::get('/procurement/user-manual/print', fn() => view('FAIMS.Procurement.prints.user-manual'))->name('procurement.user-manual.print');
    Route::get('/inventory-print/stock-card/{item}', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'stockCard'])->name('inventory.print.stock-card');
    Route::get('/inventory-print/ris/{inventory_ri}', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'ris'])->name('inventory.print.ris');
    Route::get('/inventory-print/ics/{inventory_ic}', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'ics'])->name('inventory.print.ics');
    Route::get('/inventory-print/par/{inventory_par}', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'par'])->name('inventory.print.par');
    Route::get('/inventory-print/waste-material', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'wasteMaterial'])->name('inventory.print.waste-material');
    Route::get('/inventory-print/report/{inventory_report}', [App\Http\Controllers\Inventory\InventoryPrintController::class, 'report'])->name('inventory.print.report');

    // OneApp Chatbot
    Route::post('/ai-chat', App\Http\Controllers\AI\AiChatController::class);
    Route::get('/ai-settings', [App\Http\Controllers\AI\ChatbotSettingController::class, 'show']);
    Route::post('/ai-settings', [App\Http\Controllers\AI\ChatbotSettingController::class, 'save']);
    Route::post('/ai-settings/test', [App\Http\Controllers\AI\ChatbotSettingController::class, 'test']);
    Route::delete('/ai-settings/key', [App\Http\Controllers\AI\ChatbotSettingController::class, 'clearKey']);
    Route::get('/ai-modules', [App\Http\Controllers\AI\ChatbotSettingController::class, 'modules']);
    Route::patch('/ai-modules/{key}', [App\Http\Controllers\AI\ChatbotSettingController::class, 'toggleModule'])
        ->where('key', '[a-z0-9_]+');
});

Route::middleware(['role:Asset Management Officer'])->group(function () {
    Route::resource('/buildings', App\Http\Controllers\Assets\BuildingController::class);
    Route::resource('/equipments', App\Http\Controllers\Assets\EquipmentController::class);
    Route::resource('/vehicles', App\Http\Controllers\Assets\VehicleController::class);
});

Route::middleware(['role:Document Management Officer'])->group(function () {
    Route::resource('/events', App\Http\Controllers\Trace\EventController::class);
});

Route::middleware(['role:Human Resource Officer'])->group(function () {
    Route::resource('/humanresource', App\Http\Controllers\HumanResource\DashboardController::class);
    Route::resource('/employees', App\Http\Controllers\HumanResource\EmployeeController::class);
    Route::resource('/dtrs', App\Http\Controllers\HumanResource\DtrController::class);
    Route::resource('/calendar', App\Http\Controllers\HumanResource\CalendarController::class);
    Route::resource('/payroll', App\Http\Controllers\HumanResource\PayrollController::class);
    Route::get('/payroll/{type}/{code}', [App\Http\Controllers\HumanResource\PayrollController::class, 'view']);
    Route::resource('/credits', App\Http\Controllers\HumanResource\CreditController::class);
    Route::resource('/visitors', App\Http\Controllers\HumanResource\VisitorController::class);
});

Route::resource('/surveys', App\Http\Controllers\HumanResource\SurveyController::class);
Route::resource('/approvals', App\Http\Controllers\Portal\ApprovalController::class)
    ->middleware(['auth', 'verified']);

Route::middleware(['role:Administrator'])->group(function () {
    Route::resource('/users', App\Http\Controllers\Executive\UserController::class);
    Route::resource('/references', App\Http\Controllers\Executive\ReferenceController::class);
    Route::resource('/signatories', App\Http\Controllers\Executive\SignatoryController::class);

    Route::get('/rekognition/test', [App\Http\Controllers\Executive\RekognitionController::class, 'test']);
    Route::get('/rekognition/check', [App\Http\Controllers\Executive\RekognitionController::class, 'check']);
    Route::get('/rekognition/delete', [App\Http\Controllers\Executive\RekognitionController::class, 'delete']);
    Route::get('/rekognition/create', [App\Http\Controllers\Executive\RekognitionController::class, 'create']);
    Route::get('/rekognition/search', [App\Http\Controllers\Executive\RekognitionController::class, 'search']);
    Route::get('/rekognition/collection/{id}', [App\Http\Controllers\Executive\RekognitionController::class, 'deleteCollection']);
    Route::get('/rekognition/collection/{id}/faces', [App\Http\Controllers\Executive\RekognitionController::class, 'listFaces']);
    Route::get('/rekognition/collection/{id}/face/{faceId}', [App\Http\Controllers\Executive\RekognitionController::class, 'deleteFace']);
});

Route::prefix('faims')->middleware(['auth', 'verified'])->group(function () {

    // Finance
    Route::get('/finance-dashboard', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('/finance-disbursements-obligations', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'disbursementsObligations'])->name('finance.disbursements-obligations');
    Route::resource('/finance-requests', App\Http\Controllers\FAIMS\Finance\FinanceController::class);
    Route::post('/finance-requests/{id}/comments', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'addComment']);
    Route::post('/finance-requests/{id}/attachments', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'storeAttachment']);
    Route::get('/finance-requests/{id}/attachments/{attachmentId}/preview', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'previewAttachment']);
    Route::delete('/finance-requests/{id}/attachments/{attachmentId}', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'deleteAttachment']);
    Route::post('/finance-requests/{id}/attachments/{attachmentId}/comments', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'addAttachmentComment']);
    Route::patch('/finance-requests/{id}/attachments/{attachmentId}/verify', [App\Http\Controllers\FAIMS\Finance\FinanceController::class, 'verifyAttachment']);
    Route::resource('/finance-requests-assignments', App\Http\Controllers\FAIMS\Finance\FinanceAssignmentController::class);
    Route::resource('/finance-request-types', App\Http\Controllers\FAIMS\Finance\FinanceRequestTypeController::class);
    Route::resource('/finance-documents', App\Http\Controllers\FAIMS\Finance\FinanceRequiredDocumentController::class);
    Route::resource('/finance-projects', App\Http\Controllers\FAIMS\Finance\ProjectController::class);
    Route::resource('/finance-creditors', App\Http\Controllers\FAIMS\Finance\FinanceCreditorController::class);

    // Procurement
    Route::get('/procurement-mention-notifications', [App\Http\Controllers\FAIMS\Procurement\ProcurementNotificationController::class, 'index'])
        ->middleware(['auth', 'verified']);
    Route::patch('/procurement-mention-notifications/{notification}/read', [App\Http\Controllers\FAIMS\Procurement\ProcurementNotificationController::class, 'update'])
        ->middleware(['auth', 'verified']);
    Route::get('/procurements/create-by-category', [App\Http\Controllers\FAIMS\Procurement\ProcurementController::class, 'createByCategory']);
    Route::resource('/procurements', App\Http\Controllers\FAIMS\Procurement\ProcurementController::class)->names([
        'index' => 'procurement.index',
    ]);
    Route::get('/procurement-reports', [App\Http\Controllers\FAIMS\Procurement\ProcurementReportController::class, 'index'])
        ->name('procurement.reports');
    Route::resource('/procurement-assignments', App\Http\Controllers\FAIMS\Procurement\ProcurementAssignmentController::class);
    Route::post('/procurement-ppmp/item-categories', [App\Http\Controllers\FAIMS\Procurement\ProcurementPPMPController::class, 'storeItemCategory']);
    Route::get('/procurement-ppmp/items/{item}/supporting-document', [App\Http\Controllers\FAIMS\Procurement\ProcurementPPMPController::class, 'supportingDocument'])
        ->name('procurement-ppmp.items.supporting-document');
    Route::resource('/procurement-ppmp', App\Http\Controllers\FAIMS\Procurement\ProcurementPPMPController::class)
        ->only(['index', 'store', 'show', 'update']);
    Route::post('/procurement-ppmp/{id}/comments', [App\Http\Controllers\FAIMS\Procurement\ProcurementPPMPController::class, 'storeComment']);
    Route::resource('/procurement-codes', App\Http\Controllers\FAIMS\Procurement\ProcurementCodeController::class);
    Route::get('/procurement-dashboard', [App\Http\Controllers\FAIMS\Procurement\ProcurementDashboardController::class, 'index'])->name('procurement.dashboard');

    Route::get('/procurements/create', [App\Http\Controllers\FAIMS\Procurement\ProcurementController::class, 'create']);
    Route::post('/procurements/{id}/comments', [App\Http\Controllers\FAIMS\Procurement\ProcurementCommentController::class, 'store']);
    Route::resource('/quotations', App\Http\Controllers\FAIMS\Procurement\QuotationController::class);
    Route::resource('/offers', App\Http\Controllers\FAIMS\Procurement\OfferController::class);
    Route::resource('/bac-resolutions', App\Http\Controllers\FAIMS\Procurement\BACResolutionController::class);
    Route::resource('/notice-of-awards', App\Http\Controllers\FAIMS\Procurement\NOAController::class);
    Route::resource('/purchase-orders', App\Http\Controllers\FAIMS\Procurement\POController::class);
    Route::resource('/suppliers', App\Http\Controllers\FAIMS\Procurement\SupplierController::class);
    Route::patch('/suppliers/{supplier}/approve', [App\Http\Controllers\FAIMS\Procurement\SupplierController::class, 'approve']);
    Route::resource('/responsibility-centers', App\Http\Controllers\FAIMS\Procurement\ResponsibilityCenterController::class);
    Route::resource('/modes-of-procurement', App\Http\Controllers\FAIMS\Procurement\ModeOfProcurementController::class);
    Route::get('/receiving-list', [App\Http\Controllers\FAIMS\Procurement\ReceivingDeliveryController::class, 'receivingList']);
    Route::get('/receiving-deliveries', [App\Http\Controllers\FAIMS\Procurement\ReceivingDeliveryController::class, 'index']);
    Route::put('/receiving-deliveries/{id}', [App\Http\Controllers\FAIMS\Procurement\ReceivingDeliveryController::class, 'update']);
    Route::resource('/ia-reports', App\Http\Controllers\FAIMS\Procurement\IAReportController::class);
    Route::patch('/suppliers/{supplier}/status', [App\Http\Controllers\FAIMS\Procurement\SupplierController::class, 'status']);

});

Route::get('/key-officials', [App\Http\Controllers\Public\InfoController::class, 'keyofficials']);
Route::get('/bac-committee', [App\Http\Controllers\Public\InfoController::class, 'baccommittee']);
Route::get('/iar-committee', [App\Http\Controllers\Public\InfoController::class, 'iarcommittee']);
Route::get('/mailing', [App\Http\Controllers\Public\InfoController::class, 'mailing']);
require __DIR__.'/auth.php';
