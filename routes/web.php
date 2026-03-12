<?php

use App\Http\Controllers\VelzonRoutesController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('/dtr', App\Http\Controllers\Portal\DtrController::class);
    Route::resource('/requests', App\Http\Controllers\Portal\RequestController::class);
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
Route::resource('/approvals', App\Http\Controllers\Portal\ApprovalController::class);

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

Route::get('/key-officials', [App\Http\Controllers\Public\InfoController::class, 'keyofficials']);
Route::get('/mailing', [App\Http\Controllers\Public\InfoController::class, 'mailing']);
require __DIR__.'/auth.php';
