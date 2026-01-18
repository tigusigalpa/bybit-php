<?php

/**
 * Example API Routes for Bybit Integration
 * 
 * Add these routes to your routes/api.php file
 */

use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bybit')->group(function () {
    
    // Account & Balance
    Route::get('/balance', [TradingController::class, 'getBalance']);
    Route::get('/positions', [TradingController::class, 'getPositions']);
    
    // Market Data
    Route::get('/ticker/{symbol}', [TradingController::class, 'getTicker']);
    
    // Orders
    Route::get('/orders/{symbol}', [TradingController::class, 'getOpenOrders']);
    Route::post('/order/market', [TradingController::class, 'placeMarketOrder']);
    Route::post('/order/limit', [TradingController::class, 'placeLimitOrder']);
    Route::delete('/order', [TradingController::class, 'cancelOrder']);
    
});
