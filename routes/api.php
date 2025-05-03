<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\CompletedTripController;
use App\Http\Controllers\Api\CancelTripController;
use App\Http\Controllers\Api\SelfController;
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


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/trips/{id}/complete', [CompletedTripController::class, 'completeTrip']);
    Route::post('/trips/{id}/cancel', [CancelTripController::class, 'cancelTrip']);
    Route::get('/user/profile', [SelfController::class, 'viewSelf']);
    Route::put('/user/updateProfile', [SelfController::class, 'updateSelf']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
    
});



