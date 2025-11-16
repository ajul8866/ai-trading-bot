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

                // GET REAL ORDER BOOK DEPTH FROM BINANCE - NO FAKE DATA!
                $depthData = $binance->getDepth($this->symbol, $this->depth);
                $currentPrice = $binance->getCurrentPrice($this->symbol);

                return [
                    'bids' => $depthData['bids'],
                    'asks' => $depthData['asks'],
                    'currentPrice' => $currentPrice,
                    'maxBidDepth' => $depthData['maxBidDepth'],
                    'maxAskDepth' => $depthData['maxAskDepth'],
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
