<?php

use App\Jobs\AnalyzeMarketJob;
use App\Jobs\FetchMarketDataJob;
use App\Jobs\MonitorPositionsJob;
use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Monitor positions every minute
        $schedule->job(new MonitorPositionsJob)
            ->everyMinute()
            ->name('monitor-positions')
            ->withoutOverlapping();

        // Fetch market data every 3 minutes
        $schedule->call(function () {
            $tradingPairs = json_decode(Setting::where('key', 'trading_pairs')->value('value') ?? '[]', true);
            $timeframes = json_decode(Setting::where('key', 'timeframes')->value('value') ?? '[]', true);

            if (empty($tradingPairs)) {
                $tradingPairs = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT'];
            }

            if (empty($timeframes)) {
                $timeframes = ['5m', '15m', '30m', '1h'];
            }

            // Fetch market data for all pairs and timeframes
            foreach ($tradingPairs as $symbol) {
                foreach ($timeframes as $timeframe) {
                    FetchMarketDataJob::dispatch($symbol, $timeframe);
                }
            }

            // Analyze market for each trading pair with delay to allow data fetching
            // Use delayed jobs instead of blocking sleep()
            foreach ($tradingPairs as $symbol) {
                AnalyzeMarketJob::dispatch($symbol)->delay(now()->addSeconds(15));
            }
        })
            ->everyThreeMinutes()
            ->name('fetch-and-analyze')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
