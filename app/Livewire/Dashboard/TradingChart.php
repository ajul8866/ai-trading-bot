<?php

namespace App\Livewire\Dashboard;

use App\Jobs\CacheChartDataJob;
use App\Models\ChartData;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class TradingChart extends Component
{
    public string $symbol = 'BTCUSDT';

    public string $timeframe = '5m';

    public int $limit = 200;

    public array $chartData = [];

    public float $currentPrice = 0;

    public float $priceChange = 0;

    public float $priceChangePercent = 0;

    public array $availableSymbols = [];

    public array $availableTimeframes = ['1m', '5m', '15m', '30m', '1h', '4h', '1d'];

    public bool $isLoading = true;

    public function mount(): void
    {
        $this->loadAvailableSymbols();
        $this->loadChartData();
    }

    #[On('refresh-chart')]
    public function refresh(): void
    {
        $this->loadChartData();
    }

    public function updatedSymbol(): void
    {
        $this->loadChartData();
    }

    public function updatedTimeframe(): void
    {
        $this->loadChartData();
    }

    public function loadChartData(): void
    {
        try {
            $this->isLoading = true;

            $cacheKey = "chart_data:{$this->symbol}:{$this->timeframe}";

            // Try to get from cache first
            $rawData = Cache::get($cacheKey);

            if (! $rawData) {
                // Dispatch job to fetch data
                dispatch(new CacheChartDataJob($this->symbol, $this->timeframe, $this->limit));

                // Get from database
                $rawData = ChartData::where('symbol', $this->symbol)
                    ->where('timeframe', $this->timeframe)
                    ->orderBy('timestamp', 'asc')
                    ->limit($this->limit)
                    ->get()
                    ->toArray();
            }

            // Format data for Lightweight Charts
            $this->chartData = $this->formatChartData($rawData);

            // Calculate price changes
            if (! empty($this->chartData)) {
                $latest = end($this->chartData);
                $first = reset($this->chartData);

                $this->currentPrice = $latest['close'] ?? 0;
                $openPrice = $first['open'] ?? 0;

                if ($openPrice > 0) {
                    $this->priceChange = $this->currentPrice - $openPrice;
                    $this->priceChangePercent = ($this->priceChange / $openPrice) * 100;
                }
            }

            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->isLoading = false;
            logger()->error('Failed to load chart data', [
                'symbol' => $this->symbol,
                'timeframe' => $this->timeframe,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function formatChartData(array $rawData): array
    {
        return array_map(function ($candle) {
            if (is_array($candle)) {
                return [
                    'time' => isset($candle['timestamp']) ? strtotime($candle['timestamp']) : time(),
                    'open' => (float) ($candle['open'] ?? 0),
                    'high' => (float) ($candle['high'] ?? 0),
                    'low' => (float) ($candle['low'] ?? 0),
                    'close' => (float) ($candle['close'] ?? 0),
                    'volume' => (float) ($candle['volume'] ?? 0),
                ];
            }

            // Handle ChartData model
            return [
                'time' => $candle['timestamp']->timestamp ?? time(),
                'open' => (float) ($candle['open'] ?? 0),
                'high' => (float) ($candle['high'] ?? 0),
                'low' => (float) ($candle['low'] ?? 0),
                'close' => (float) ($candle['close'] ?? 0),
                'volume' => (float) ($candle['volume'] ?? 0),
            ];
        }, $rawData);
    }

    private function loadAvailableSymbols(): void
    {
        $tradingPairs = Setting::where('key', 'trading_pairs')->first();

        if ($tradingPairs && $tradingPairs->value) {
            $this->availableSymbols = is_array($tradingPairs->value)
                ? $tradingPairs->value
                : json_decode($tradingPairs->value, true);
        } else {
            $this->availableSymbols = [
                'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT',
                'ADAUSDT', 'DOTUSDT', 'MATICUSDT', 'LINKUSDT', 'AVAXUSDT',
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.trading-chart');
    }
}
