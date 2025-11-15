<?php

namespace App\Jobs;

use App\Models\MarketData;
use App\Services\BinanceService;
use App\Services\TechnicalIndicatorService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchMarketDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $symbol,
        public string $timeframe,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        BinanceService $binanceService,
        TechnicalIndicatorService $indicatorService
    ): void {
        try {
            Log::info('Fetching market data', ['symbol' => $this->symbol, 'timeframe' => $this->timeframe]);

            // Fetch OHLCV data from Binance
            $ohlcvData = $binanceService->getOHLCV($this->symbol, $this->timeframe, 100);

            if (empty($ohlcvData)) {
                Log::warning('No OHLCV data received', ['symbol' => $this->symbol, 'timeframe' => $this->timeframe]);

                return;
            }

            // Calculate technical indicators
            $indicators = $indicatorService->calculateAllIndicators($ohlcvData);

            // Get the latest candle
            $latestCandle = end($ohlcvData);

            // Store in database
            MarketData::updateOrCreate(
                [
                    'symbol' => $this->symbol,
                    'timeframe' => $this->timeframe,
                    'candle_time' => Carbon::createFromTimestampMs($latestCandle['timestamp']),
                ],
                [
                    'open' => $latestCandle['open'],
                    'high' => $latestCandle['high'],
                    'low' => $latestCandle['low'],
                    'close' => $latestCandle['close'],
                    'volume' => $latestCandle['volume'],
                    'indicators' => $indicators,
                ]
            );

            // Cache the data for quick access (cache for 3 minutes)
            $cacheKey = "market_data:{$this->symbol}:{$this->timeframe}";
            Cache::put($cacheKey, [
                'ohlcv' => $ohlcvData,
                'indicators' => $indicators,
                'timestamp' => now(),
            ], 180);

            Log::info('Market data fetched successfully', [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'close' => $latestCandle['close'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching market data', [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
