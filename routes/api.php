<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurrencyRateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MarketController;
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


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/markets', [MarketController::class, 'index']); 
Route::post('/send-email', [ContactController::class, 'sendEmail']);

Route::prefix('currency-rates')->group(function () {
    Route::get('/', [CurrencyRateController::class, 'index']);
    Route::get('/current', [CurrencyRateController::class, 'current']);
    Route::get('/monthly', [CurrencyRateController::class, 'monthly']);
    Route::get('/variations', [CurrencyRateController::class, 'variations']);
    Route::get('/{id}', [CurrencyRateController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/markets', [MarketController::class, 'create']);

    // Rotas para cotações monetárias
    Route::prefix('currency-rates')->group(function () {
        Route::post('/', [CurrencyRateController::class, 'store']);
        Route::put('/{id}', [CurrencyRateController::class, 'update']);
        Route::delete('/{id}', [CurrencyRateController::class, 'destroy']);
    });
});