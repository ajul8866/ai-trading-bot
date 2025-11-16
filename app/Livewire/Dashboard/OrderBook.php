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

                // GET REAL ORDER BOOK FROM BINANCE - NO FAKE DATA!
                $depthData = $binance->getDepth($this->symbol, $this->depth);
                $currentPrice = $binance->getCurrentPrice($this->symbol);

                // Transform to OrderBook format (with 'size' instead of 'quantity')
                $bids = array_map(function ($bid) {
                    return [
                        'price' => $bid['price'],
                        'size' => $bid['quantity'],
                        'total' => 0, // Will be calculated after
                    ];
                }, $depthData['bids']);

                $asks = array_map(function ($ask) {
                    return [
                        'price' => $ask['price'],
                        'size' => $ask['quantity'],
                        'total' => 0, // Will be calculated after
                    ];
                }, $depthData['asks']);

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
