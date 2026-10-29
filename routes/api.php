<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\LowStockController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/customers/orders', [CustomerOrderController::class, 'index']);
Route::get('/products/low-stock', [LowStockController::class, 'index']);