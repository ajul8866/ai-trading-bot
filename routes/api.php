<?php

use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TradeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Bot Control
    Route::get('bot/status', [BotController::class, 'status'])->name('api.bot.status');
    Route::post('bot/start', [BotController::class, 'start'])->name('api.bot.start');
    Route::post('bot/stop', [BotController::class, 'stop'])->name('api.bot.stop');

    // Trades
    Route::get('trades', [TradeController::class, 'index'])->name('api.trades.index');
    Route::get('trades/{trade}', [TradeController::class, 'show'])->name('api.trades.show');

    // Positions
    Route::get('positions', [PositionController::class, 'index'])->name('api.positions.index');

    // Performance
    Route::get('performance', [PerformanceController::class, 'index'])->name('api.performance.index');
    Route::get('performance/metrics', [PerformanceController::class, 'metrics'])->name('api.performance.metrics');

    // Charts
    Route::get('chart/{symbol}', [ChartController::class, 'show'])->name('api.chart.show');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('api.settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('api.settings.update');
    Route::get('settings/{key}', [SettingController::class, 'show'])->name('api.settings.show');
});
