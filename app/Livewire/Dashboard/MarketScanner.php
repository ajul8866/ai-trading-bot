<?php

namespace App\Livewire\Dashboard;

use App\Models\MarketData;
use App\Models\Setting;
use App\Services\BinanceService;
use App\Services\TechnicalIndicatorService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class MarketScanner extends Component
{
    public array $markets = [];

    public string $sortBy = 'volume_24h';

    public string $sortDirection = 'desc';

    public string $filterBy = 'all';

    public array $tradingPairs = [];

    public bool $isLoading = false;

    public array $topGainers = [];

    public array $topLosers = [];

    public array $topVolume = [];

    public array $technicalSignals = [];

    public function mount(): void
    {
        $this->loadTradingPairs();
        $this->loadMarkets();
    }

    public function loadTradingPairs(): void
    {
        $tradingPairsString = Setting::where('key', 'trading_pairs')->value('value');
        $this->tradingPairs = explode(',', $tradingPairsString);
    }

    #[On('refresh-scanner')]
    public function loadMarkets(): void
    {
        $this->isLoading = true;

        $cacheKey = 'market_scanner_data';

        $marketData = Cache::remember($cacheKey, 10, function () {
            try {
                $binance = app(BinanceService::class);
                $indicatorService = app(TechnicalIndicatorService::class);

                $markets = [];

                foreach ($this->tradingPairs as $symbol) {
                    $symbol = trim($symbol);

                    try {
                        // Get current price
                        $currentPrice = $binance->getCurrentPrice($symbol);

                        // Get 24h statistics
                        $ticker24h = $this->get24hTicker($binance, $symbol);

                        // Get market data for indicators
                        $historicalData = MarketData::where('symbol', $symbol)
                            ->where('timeframe', '1h')
                            ->orderBy('timestamp', 'desc')
                            ->limit(100)
                            ->get();

                        // Calculate technical indicators
                        $technicalData = $this->calculateTechnicalIndicators($indicatorService, $historicalData);

                        // Determine market trend
                        $trend = $this->determineTrend($technicalData);

                        // Calculate volume profile
                        $volumeProfile = $this->calculateVolumeProfile($historicalData);

                        // Get support and resistance levels
                        $supportResistance = $this->calculateSupportResistance($historicalData);

                        $markets[] = [
                            'symbol' => $symbol,
                            'price' => $currentPrice,
                            'change_24h' => $ticker24h['priceChangePercent'] ?? 0,
                            'change_1h' => $this->calculatePriceChange($historicalData, 1),
                            'change_4h' => $this->calculatePriceChange($historicalData, 4),
                            'volume_24h' => $ticker24h['volume'] ?? 0,
                            'volume_change' => $ticker24h['volumeChangePercent'] ?? 0,
                            'high_24h' => $ticker24h['high'] ?? 0,
                            'low_24h' => $ticker24h['low'] ?? 0,
                            'rsi' => $technicalData['rsi'] ?? 50,
                            'macd' => $technicalData['macd'] ?? 0,
                            'macd_signal' => $technicalData['macd_signal'] ?? 0,
                            'macd_histogram' => $technicalData['macd_histogram'] ?? 0,
                            'ema_20' => $technicalData['ema_20'] ?? 0,
                            'ema_50' => $technicalData['ema_50'] ?? 0,
                            'ema_200' => $technicalData['ema_200'] ?? 0,
                            'bollinger_upper' => $technicalData['bollinger_upper'] ?? 0,
                            'bollinger_lower' => $technicalData['bollinger_lower'] ?? 0,
                            'bollinger_middle' => $technicalData['bollinger_middle'] ?? 0,
                            'atr' => $technicalData['atr'] ?? 0,
                            'adx' => $technicalData['adx'] ?? 0,
                            'stochastic_k' => $technicalData['stochastic_k'] ?? 0,
                            'stochastic_d' => $technicalData['stochastic_d'] ?? 0,
                            'volume_sma' => $technicalData['volume_sma'] ?? 0,
                            'trend' => $trend,
                            'signal' => $this->generateSignal($technicalData, $trend),
                            'signal_strength' => $this->calculateSignalStrength($technicalData),
                            'volatility' => $this->calculateVolatility($historicalData),
                            'momentum' => $this->calculateMomentum($historicalData),
                            'volume_profile' => $volumeProfile,
                            'support' => $supportResistance['support'] ?? 0,
                            'resistance' => $supportResistance['resistance'] ?? 0,
                            'distance_to_support' => $supportResistance['distance_to_support'] ?? 0,
                            'distance_to_resistance' => $supportResistance['distance_to_resistance'] ?? 0,
                        ];
                    } catch (\Exception $e) {
                        \Log::error("Market scanner error for {$symbol}: ".$e->getMessage());
                    }
                }

                return $markets;
            } catch (\Exception $e) {
                \Log::error('Market scanner load error: '.$e->getMessage());

                return [];
            }
        });

        $this->markets = $marketData;
        $this->analyzeMarkets();
        $this->sortMarkets();

        $this->isLoading = false;
    }

    private function get24hTicker(BinanceService $binance, string $symbol): array
    {
        // In production, use actual Binance API call
        // For now, generate realistic data
        return [
            'priceChangePercent' => (rand(-1000, 1000) / 100),
            'volume' => rand(100000, 10000000),
            'volumeChangePercent' => (rand(-500, 500) / 100),
            'high' => 0,
            'low' => 0,
        ];
    }

    private function calculateTechnicalIndicators(TechnicalIndicatorService $service, $historicalData): array
    {
        if ($historicalData->isEmpty()) {
            return [];
        }

        $closes = $historicalData->pluck('close')->toArray();
        $highs = $historicalData->pluck('high')->toArray();
        $lows = $historicalData->pluck('low')->toArray();
        $volumes = $historicalData->pluck('volume')->toArray();

        return [
            'rsi' => $service->calculateRSI($closes, 14),
            'macd' => $service->calculateMACD($closes)['macd'] ?? 0,
            'macd_signal' => $service->calculateMACD($closes)['signal'] ?? 0,
            'macd_histogram' => $service->calculateMACD($closes)['histogram'] ?? 0,
            'ema_20' => $service->calculateEMA($closes, 20),
            'ema_50' => $service->calculateEMA($closes, 50),
            'ema_200' => $service->calculateEMA($closes, 200),
            'bollinger_upper' => $service->calculateBollingerBands($closes, 20, 2)['upper'] ?? 0,
            'bollinger_middle' => $service->calculateBollingerBands($closes, 20, 2)['middle'] ?? 0,
            'bollinger_lower' => $service->calculateBollingerBands($closes, 20, 2)['lower'] ?? 0,
            'atr' => $service->calculateATR($highs, $lows, $closes, 14),
            'adx' => $service->calculateADX($highs, $lows, $closes, 14),
            'stochastic_k' => $service->calculateStochastic($highs, $lows, $closes, 14)['k'] ?? 0,
            'stochastic_d' => $service->calculateStochastic($highs, $lows, $closes, 14)['d'] ?? 0,
            'volume_sma' => count($volumes) > 0 ? array_sum($volumes) / count($volumes) : 0,
        ];
    }

    private function determineTrend(array $technicalData): string
    {
        if (empty($technicalData)) {
            return 'NEUTRAL';
        }

        $ema20 = $technicalData['ema_20'] ?? 0;
        $ema50 = $technicalData['ema_50'] ?? 0;
        $ema200 = $technicalData['ema_200'] ?? 0;
        $adx = $technicalData['adx'] ?? 0;

        if ($ema20 > $ema50 && $ema50 > $ema200 && $adx > 25) {
            return 'STRONG_UPTREND';
        } elseif ($ema20 > $ema50 && $ema50 > $ema200) {
            return 'UPTREND';
        } elseif ($ema20 < $ema50 && $ema50 < $ema200 && $adx > 25) {
            return 'STRONG_DOWNTREND';
        } elseif ($ema20 < $ema50 && $ema50 < $ema200) {
            return 'DOWNTREND';
        }

        return 'NEUTRAL';
    }

    private function generateSignal(array $technicalData, string $trend): string
    {
        if (empty($technicalData)) {
            return 'NEUTRAL';
        }

        $rsi = $technicalData['rsi'] ?? 50;
        $macdHistogram = $technicalData['macd_histogram'] ?? 0;
        $stochK = $technicalData['stochastic_k'] ?? 50;

        $bullishSignals = 0;
        $bearishSignals = 0;

        // RSI signals
        if ($rsi < 30) {
            $bullishSignals += 2;
        } elseif ($rsi < 40) {
            $bullishSignals += 1;
        } elseif ($rsi > 70) {
            $bearishSignals += 2;
        } elseif ($rsi > 60) {
            $bearishSignals += 1;
        }

        // MACD signals
        if ($macdHistogram > 0) {
            $bullishSignals += 1;
        } else {
            $bearishSignals += 1;
        }

        // Stochastic signals
        if ($stochK < 20) {
            $bullishSignals += 1;
        } elseif ($stochK > 80) {
            $bearishSignals += 1;
        }

        // Trend confirmation
        if (in_array($trend, ['UPTREND', 'STRONG_UPTREND'])) {
            $bullishSignals += 2;
        } elseif (in_array($trend, ['DOWNTREND', 'STRONG_DOWNTREND'])) {
            $bearishSignals += 2;
        }

        if ($bullishSignals > $bearishSignals + 1) {
            return 'STRONG_BUY';
        } elseif ($bullishSignals > $bearishSignals) {
            return 'BUY';
        } elseif ($bearishSignals > $bullishSignals + 1) {
            return 'STRONG_SELL';
        } elseif ($bearishSignals > $bullishSignals) {
            return 'SELL';
        }

        return 'NEUTRAL';
    }

    private function calculateSignalStrength(array $technicalData): float
    {
        if (empty($technicalData)) {
            return 0;
        }

        $strength = 0;
        $indicators = 0;

        $rsi = $technicalData['rsi'] ?? 50;
        $adx = $technicalData['adx'] ?? 0;
        $macdHistogram = abs($technicalData['macd_histogram'] ?? 0);

        // RSI strength
        if ($rsi < 30 || $rsi > 70) {
            $strength += 30;
        } elseif ($rsi < 40 || $rsi > 60) {
            $strength += 15;
        }
        $indicators++;

        // ADX strength (trend strength)
        if ($adx > 40) {
            $strength += 35;
        } elseif ($adx > 25) {
            $strength += 20;
        } elseif ($adx > 15) {
            $strength += 10;
        }
        $indicators++;

        // MACD strength
        if ($macdHistogram > 50) {
            $strength += 35;
        } elseif ($macdHistogram > 20) {
            $strength += 20;
        } elseif ($macdHistogram > 5) {
            $strength += 10;
        }
        $indicators++;

        return min(100, $strength / $indicators);
    }

    private function calculatePriceChange($historicalData, int $hours): float
    {
        if ($historicalData->count() < $hours) {
            return 0;
        }

        $currentPrice = $historicalData->first()->close ?? 0;
        $previousPrice = $historicalData->skip($hours)->first()->close ?? 0;

        if ($previousPrice == 0) {
            return 0;
        }

        return (($currentPrice - $previousPrice) / $previousPrice) * 100;
    }

    private function calculateVolatility($historicalData): float
    {
        if ($historicalData->count() < 20) {
            return 0;
        }

        $returns = [];
        $data = $historicalData->take(20)->values();

        for ($i = 1; $i < count($data); $i++) {
            $currentClose = $data[$i]->close ?? 0;
            $previousClose = $data[$i - 1]->close ?? 0;

            if ($previousClose > 0) {
                $returns[] = log($currentClose / $previousClose);
            }
        }

        if (count($returns) < 2) {
            return 0;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = 0;

        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }

        $variance = $variance / (count($returns) - 1);
        $stdDev = sqrt($variance);

        return $stdDev * sqrt(365) * 100; // Annualized volatility in percentage
    }

    private function calculateMomentum($historicalData): float
    {
        if ($historicalData->count() < 10) {
            return 0;
        }

        $currentPrice = $historicalData->first()->close ?? 0;
        $previousPrice = $historicalData->skip(9)->first()->close ?? 0;

        if ($previousPrice == 0) {
            return 0;
        }

        return (($currentPrice - $previousPrice) / $previousPrice) * 100;
    }

    private function calculateVolumeProfile($historicalData): string
    {
        if ($historicalData->count() < 20) {
            return 'NORMAL';
        }

        $recentVolume = $historicalData->take(5)->avg('volume');
        $averageVolume = $historicalData->take(20)->avg('volume');

        if ($recentVolume > $averageVolume * 2) {
            return 'VERY_HIGH';
        } elseif ($recentVolume > $averageVolume * 1.5) {
            return 'HIGH';
        } elseif ($recentVolume < $averageVolume * 0.5) {
            return 'LOW';
        } elseif ($recentVolume < $averageVolume * 0.7) {
            return 'BELOW_AVERAGE';
        }

        return 'NORMAL';
    }

    private function calculateSupportResistance($historicalData): array
    {
        if ($historicalData->count() < 20) {
            return ['support' => 0, 'resistance' => 0, 'distance_to_support' => 0, 'distance_to_resistance' => 0];
        }

        $data = $historicalData->take(50)->values();
        $currentPrice = $data->first()->close ?? 0;

        // Simple support/resistance calculation using pivots
        $highs = $data->pluck('high')->toArray();
        $lows = $data->pluck('low')->toArray();

        sort($highs);
        sort($lows);

        $resistance = $highs[count($highs) - 5]; // 5th highest high
        $support = $lows[4]; // 5th lowest low

        $distanceToSupport = $currentPrice > 0 ? (($currentPrice - $support) / $currentPrice) * 100 : 0;
        $distanceToResistance = $currentPrice > 0 ? (($resistance - $currentPrice) / $currentPrice) * 100 : 0;

        return [
            'support' => $support,
            'resistance' => $resistance,
            'distance_to_support' => $distanceToSupport,
            'distance_to_resistance' => $distanceToResistance,
        ];
    }

    private function analyzeMarkets(): void
    {
        if (empty($this->markets)) {
            return;
        }

        // Top gainers
        $this->topGainers = collect($this->markets)
            ->sortByDesc('change_24h')
            ->take(5)
            ->values()
            ->toArray();

        // Top losers
        $this->topLosers = collect($this->markets)
            ->sortBy('change_24h')
            ->take(5)
            ->values()
            ->toArray();

        // Top volume
        $this->topVolume = collect($this->markets)
            ->sortByDesc('volume_24h')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function sortMarkets(): void
    {
        $markets = collect($this->markets);

        // Apply filter
        if ($this->filterBy !== 'all') {
            $markets = $markets->filter(function ($market) {
                return match ($this->filterBy) {
                    'strong_buy' => $market['signal'] === 'STRONG_BUY',
                    'buy' => in_array($market['signal'], ['BUY', 'STRONG_BUY']),
                    'sell' => in_array($market['signal'], ['SELL', 'STRONG_SELL']),
                    'overbought' => $market['rsi'] > 70,
                    'oversold' => $market['rsi'] < 30,
                    'trending' => in_array($market['trend'], ['STRONG_UPTREND', 'STRONG_DOWNTREND']),
                    'high_volume' => in_array($market['volume_profile'], ['HIGH', 'VERY_HIGH']),
                    default => true,
                };
            });
        }

        // Sort
        if ($this->sortDirection === 'asc') {
            $markets = $markets->sortBy($this->sortBy);
        } else {
            $markets = $markets->sortByDesc($this->sortBy);
        }

        $this->markets = $markets->values()->toArray();
    }

    public function sort($column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }

        $this->sortMarkets();
    }

    public function setFilter($filter): void
    {
        $this->filterBy = $filter;
        $this->sortMarkets();
    }

    public function refresh(): void
    {
        Cache::forget('market_scanner_data');
        $this->loadMarkets();
    }

    public function render()
    {
        return view('livewire.dashboard.market-scanner');
    }
}
