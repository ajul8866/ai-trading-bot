<?php

namespace App\Livewire\Dashboard;

use App\Services\BinanceService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderBook extends Component
{
    public string $symbol = 'BTCUSDT';

    public array $bids = [];

    public array $asks = [];

    public float $spread = 0;

    public float $spreadPercent = 0;

    public int $depth = 20;

    public float $bidTotal = 0;

    public float $askTotal = 0;

    public function mount(): void
    {
        $this->loadOrderBook();
    }

    #[On('refresh-orderbook')]
    public function loadOrderBook(): void
    {
        $cacheKey = "orderbook:{$this->symbol}:{$this->depth}";

        $orderBookData = Cache::remember($cacheKey, 2, function () {
            try {
                $binance = app(BinanceService::class);
                $currentPrice = $binance->getCurrentPrice($this->symbol);

                // Generate realistic order book data (in production, fetch from Binance WebSocket)
                $bids = [];
                $asks = [];

                for ($i = 0; $i < $this->depth; $i++) {
                    // Bids are below current price
                    $bidPrice = $currentPrice * (1 - (($i + 1) * 0.0001));
                    $bidSize = rand(100, 10000) / 100; // Random size between 1 and 100
                    $bids[] = [
                        'price' => $bidPrice,
                        'size' => $bidSize,
                        'total' => 0, // Will be calculated below
                    ];

                    // Asks are above current price
                    $askPrice = $currentPrice * (1 + (($i + 1) * 0.0001));
                    $askSize = rand(100, 10000) / 100;
                    $asks[] = [
                        'price' => $askPrice,
                        'size' => $askSize,
                        'total' => 0,
                    ];
                }

                return [
                    'bids' => $bids,
                    'asks' => $asks,
                    'currentPrice' => $currentPrice,
                ];
            } catch (\Exception $e) {
                \Log::error('OrderBook load error: '.$e->getMessage());

                return [
                    'bids' => [],
                    'asks' => [],
                    'currentPrice' => 0,
                ];
            }
        });

        $this->bids = $orderBookData['bids'] ?? [];
        $this->asks = $orderBookData['asks'] ?? [];

        // Calculate cumulative totals
        $bidCumulative = 0;
        foreach ($this->bids as $index => $bid) {
            $bidCumulative += $bid['size'];
            $this->bids[$index]['total'] = $bidCumulative;
        }

        $askCumulative = 0;
        foreach ($this->asks as $index => $ask) {
            $askCumulative += $ask['size'];
            $this->asks[$index]['total'] = $askCumulative;
        }

        $this->bidTotal = $bidCumulative;
        $this->askTotal = $askCumulative;

        // Calculate spread
        if (! empty($this->bids) && ! empty($this->asks)) {
            $bestBid = $this->bids[0]['price'];
            $bestAsk = $this->asks[0]['price'];
            $this->spread = $bestAsk - $bestBid;
            $this->spreadPercent = (($this->spread / $bestBid) * 100);
        }
    }

    public function updatedSymbol(): void
    {
        $this->loadOrderBook();
    }

    public function render()
    {
        return view('livewire.dashboard.order-book');
    }
}
