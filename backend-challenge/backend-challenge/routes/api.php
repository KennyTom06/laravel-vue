<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Amortizations endpoints
Route::get('/amortizations', [\App\Http\Controllers\AmortizationsController::class, 'index']);
Route::get('/amortizations/{id}', [\App\Http\Controllers\AmortizationsController::class, 'show']);
Route::put('/amortizations/{id}', [\App\Http\Controllers\AmortizationsController::class, 'update']);

// Payments endpoint
Route::get('/payments', [\App\Http\Controllers\PaymentsController::class, 'index']);

Route::post('/amortizations/pay', [\App\Http\Controllers\AmortizationPaymentController::class, 'pay']);

// Projects endpoints
Route::get('/projects', [\App\Http\Controllers\ProjectsController::class, 'index']);
Route::post('/projects', [\App\Http\Controllers\ProjectsController::class, 'store']);
Route::put('/projects/{project}', [\App\Http\Controllers\ProjectsController::class, 'update']);

// Promoters endpoint
Route::get('/promoters', [\App\Http\Controllers\PromotersController::class, 'index']);
