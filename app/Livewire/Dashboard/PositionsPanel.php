<?php

namespace App\Livewire\Dashboard;

use App\Models\Trade;
use App\Services\BinanceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class PositionsPanel extends Component
{
    public Collection $positions;

    public float $totalUnrealizedPnl = 0;

    public int $totalPositions = 0;

    public float $totalInvestment = 0;

    public bool $isLoading = true;

    public function mount(): void
    {
        $this->positions = collect();
        $this->loadPositions();
    }

    #[On('refresh-positions')]
    public function refresh(): void
    {
        $this->loadPositions();
    }

    public function loadPositions(): void
    {
        try {
            $this->isLoading = true;

            $binance = app(BinanceService::class);

            // Get open positions from database
            $openTrades = Trade::where('status', 'OPEN')
                ->with('aiDecision')
                ->orderBy('opened_at', 'desc')
                ->get();

            // Enrich with current market data
            $this->positions = $openTrades->map(function ($trade) use ($binance) {
                $cacheKey = "price:{$trade->symbol}";

                $currentPrice = Cache::remember($cacheKey, 5, function () use ($binance, $trade) {
                    return $binance->getCurrentPrice($trade->symbol);
                });

                // Calculate unrealized P&L
                $unrealizedPnl = 0;
                $unrealizedPnlPercent = 0;

                if ($currentPrice > 0 && $trade->entry_price > 0) {
                    if (in_array($trade->side, ['BUY', 'LONG'])) {
                        $unrealizedPnl = ($currentPrice - $trade->entry_price) * $trade->quantity;
                    } else {
                        $unrealizedPnl = ($trade->entry_price - $currentPrice) * $trade->quantity;
                    }

                    $unrealizedPnlPercent = ($unrealizedPnl / ($trade->entry_price * $trade->quantity)) * 100;
                }

                // Calculate distance to stop loss and take profit
                $distanceToSL = 0;
                $distanceToTP = 0;

                if ($trade->stop_loss > 0) {
                    $distanceToSL = abs(($currentPrice - $trade->stop_loss) / $currentPrice) * 100;
                }

                if ($trade->take_profit > 0) {
                    $distanceToTP = abs(($trade->take_profit - $currentPrice) / $currentPrice) * 100;
                }

                return (object) [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'side' => $trade->side,
                    'entry_price' => $trade->entry_price,
                    'current_price' => $currentPrice,
                    'quantity' => $trade->quantity,
                    'leverage' => $trade->leverage,
                    'stop_loss' => $trade->stop_loss,
                    'take_profit' => $trade->take_profit,
                    'unrealized_pnl' => $unrealizedPnl,
                    'unrealized_pnl_percent' => $unrealizedPnlPercent,
                    'distance_to_sl' => $distanceToSL,
                    'distance_to_tp' => $distanceToTP,
                    'opened_at' => $trade->opened_at,
                    'duration' => $trade->opened_at->diffForHumans(null, true),
                    'ai_confidence' => $trade->aiDecision->confidence ?? 0,
                ];
            });

            // Calculate totals
            $this->totalPositions = $this->positions->count();
            $this->totalUnrealizedPnl = $this->positions->sum('unrealized_pnl');
            $this->totalInvestment = $this->positions->sum(function ($pos) {
                return $pos->entry_price * $pos->quantity;
            });

            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->isLoading = false;
            logger()->error('Failed to load positions', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.positions-panel');
    }
}
