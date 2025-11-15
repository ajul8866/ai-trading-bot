<?php

namespace App\Livewire\Dashboard;

use App\Services\BinanceService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class MarketDepth extends Component
{
    public string $symbol = 'BTCUSDT';

    public array $depthData = [];

    public int $depth = 50;

    public float $maxBidDepth = 0;

    public float $maxAskDepth = 0;

    public float $midPrice = 0;

    public function mount(): void
    {
        $this->loadDepthData();
    }

    #[On('refresh-depth')]
    public function loadDepthData(): void
    {
        $cacheKey = "market_depth:{$this->symbol}:{$this->depth}";

        $depthData = Cache::remember($cacheKey, 3, function () {
            try {
                $binance = app(BinanceService::class);
                $currentPrice = $binance->getCurrentPrice($this->symbol);

                $bids = [];
                $asks = [];

                // Generate realistic depth data
                $bidCumulative = 0;
                for ($i = 0; $i < $this->depth; $i++) {
                    $bidPrice = $currentPrice * (1 - (($i + 1) * 0.0002));
                    $bidSize = rand(500, 5000) / 10;
                    $bidCumulative += $bidSize;

                    $bids[] = [
                        'price' => $bidPrice,
                        'cumulative' => $bidCumulative,
                    ];
                }

                $askCumulative = 0;
                for ($i = 0; $i < $this->depth; $i++) {
                    $askPrice = $currentPrice * (1 + (($i + 1) * 0.0002));
                    $askSize = rand(500, 5000) / 10;
                    $askCumulative += $askSize;

                    $asks[] = [
                        'price' => $askPrice,
                        'cumulative' => $askCumulative,
                    ];
                }

                return [
                    'bids' => $bids,
                    'asks' => $asks,
                    'currentPrice' => $currentPrice,
                    'maxBidDepth' => $bidCumulative,
                    'maxAskDepth' => $askCumulative,
                ];
            } catch (\Exception $e) {
                \Log::error('MarketDepth load error: '.$e->getMessage());

                return [
                    'bids' => [],
                    'asks' => [],
                    'currentPrice' => 0,
                    'maxBidDepth' => 0,
                    'maxAskDepth' => 0,
                ];
            }
        });

        $this->depthData = $depthData;
        $this->maxBidDepth = $depthData['maxBidDepth'];
        $this->maxAskDepth = $depthData['maxAskDepth'];
        $this->midPrice = $depthData['currentPrice'];
    }

    public function updatedSymbol(): void
    {
        $this->loadDepthData();
    }

    public function render()
    {
        return view('livewire.dashboard.market-depth');
    }
}
