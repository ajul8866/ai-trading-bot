<?php

namespace App\Jobs;

use App\Models\ChartData;
use App\Models\Setting;
use App\Services\BinanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheChartDataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $symbol,
        public string $timeframe,
        public int $limit = 100
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BinanceService $binance): void
    {
        try {
            // Fetch OHLCV data from Binance
            $ohlcvData = $binance->getOHLCV($this->symbol, $this->timeframe, $this->limit);

            if (empty($ohlcvData)) {
                Log::warning('No OHLCV data received', [
                    'symbol' => $this->symbol,
                    'timeframe' => $this->timeframe,
                ]);

                return;
            }

            // Store in database and cache
            foreach ($ohlcvData as $candle) {
                ChartData::updateOrCreate(
                    [
                        'symbol' => $this->symbol,
                        'timeframe' => $this->timeframe,
                        'timestamp' => \Carbon\Carbon::createFromTimestampMs($candle['timestamp']),
                    ],
                    [
                        'open' => $candle['open'],
                        'high' => $candle['high'],
                        'low' => $candle['low'],
                        'close' => $candle['close'],
                        'volume' => $candle['volume'],
                    ]
                );
            }

            // Cache the data
            $cacheTtl = Setting::where('key', 'cache_ttl_charts')->value('value') ?? 300;
            $cacheKey = "chart_data:{$this->symbol}:{$this->timeframe}";

            Cache::put($cacheKey, $ohlcvData, now()->addSeconds((int) $cacheTtl));

            Log::info('Chart data cached successfully', [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'candles' => count($ohlcvData),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cache chart data', [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
