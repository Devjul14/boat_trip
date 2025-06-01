<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Api\CompletedTripController;
use App\Http\Controllers\Api\CancelTripController;
use App\Http\Controllers\Api\SelfController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\InvoiceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/trips/{id}/complete', [CompletedTripController::class, 'completeTrip']);
    Route::post('/trips/{id}/cancel', [CancelTripController::class, 'cancelTrip']);
    Route::get('/user/profile', [SelfController::class, 'viewSelf']);
    Route::put('/user/updateProfile', [SelfController::class, 'updateSelf']);
    Route::get('/tickets/search', [TicketController::class, 'search']);
    Route::put('/admin/tickets/{id}/status-update', [TicketController::class, 'statusUpdate']);
    Route::get('/trips/search', [TripController::class, 'search']);
    Route::post('/invoice/send/{invoiceId}', [InvoiceController::class, 'sendInvoice']);
    Route::post('/password-change', [PasswordResetController::class, 'changePassword']);

});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
    
});



