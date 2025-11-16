<?php

use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Middleware\EnsureApiAuthenticated;
use App\Http\Middleware\RateLimitApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - PRODUCTION SECURED
|--------------------------------------------------------------------------
|
| SECURITY FEATURES:
| - Authentication via X-API-Key header (set API_ACCESS_KEY in .env)
| - Rate limiting: 100 requests per minute per IP
| - Public routes: health check only
| - Protected routes: all trading operations
|
| SETUP:
| 1. Generate strong API key: openssl rand -hex 32
| 2. Add to .env: API_ACCESS_KEY=your_generated_key
| 3. Include in requests: X-API-Key: your_generated_key
|
*/

// Health check endpoint (public, no auth required)
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
        'services' => [
            'database' => \DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'cache' => \Cache::has('health_check') || \Cache::put('health_check', true, 10) ? 'working' : 'failed',
        ],
    ]);
})->name('api.health');

// Protected API routes - require authentication and rate limiting
Route::prefix('v1')
    ->middleware([EnsureApiAuthenticated::class, RateLimitApi::class . ':100'])
    ->group(function () {
        // Bot Control (Critical - Write Operations)
        Route::get('bot/status', [BotController::class, 'status'])->name('api.bot.status');
        Route::post('bot/start', [BotController::class, 'start'])->name('api.bot.start');
        Route::post('bot/stop', [BotController::class, 'stop'])->name('api.bot.stop');

        // Trades (Read Operations)
        Route::get('trades', [TradeController::class, 'index'])->name('api.trades.index');
        Route::get('trades/{trade}', [TradeController::class, 'show'])->name('api.trades.show');

        // Positions (Read Operations)
        Route::get('positions', [PositionController::class, 'index'])->name('api.positions.index');

        // Performance (Read Operations)
        Route::get('performance', [PerformanceController::class, 'index'])->name('api.performance.index');
        Route::get('performance/metrics', [PerformanceController::class, 'metrics'])->name('api.performance.metrics');

        // Charts (Read Operations)
        Route::get('chart/{symbol}', [ChartController::class, 'show'])->name('api.chart.show');

        // Settings (Critical - Write Operations)
        Route::get('settings', [SettingController::class, 'index'])->name('api.settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('api.settings.update');
        Route::get('settings/{key}', [SettingController::class, 'show'])->name('api.settings.show');
    });
