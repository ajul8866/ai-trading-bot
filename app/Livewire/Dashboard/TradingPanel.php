<?php

namespace App\Livewire\Dashboard;

use App\Models\Setting;
use App\Models\Trade;
use App\Services\BinanceService;
use App\Services\RiskManagementService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class TradingPanel extends Component
{
    public string $symbol = 'BTCUSDT';

    public string $side = 'BUY';

    public string $orderType = 'MARKET';

    public float $quantity = 0;

    public float $price = 0;

    public float $stopLoss = 0;

    public float $takeProfit = 0;

    public int $leverage = 10;

    public float $currentPrice = 0;

    public float $accountBalance = 0;

    public float $availableMargin = 0;

    public float $usedMargin = 0;

    public array $tradingPairs = [];

    public array $quickSizes = [10, 25, 50, 75, 100];

    public float $riskPercentage = 2;

    public float $riskAmount = 0;

    public float $positionValue = 0;

    public float $requiredMargin = 0;

    public float $potentialProfit = 0;

    public float $potentialLoss = 0;

    public float $riskRewardRatio = 0;

    public bool $isCalculating = false;

    public bool $isSubmitting = false;

    public array $recentOrders = [];

    public string $orderError = '';

    public string $orderSuccess = '';

    public function mount(): void
    {
        $this->loadTradingPairs();
        $this->loadAccountInfo();
        $this->updateCurrentPrice();
        $this->loadRecentOrders();
        $this->calculatePosition();
    }

    public function loadTradingPairs(): void
    {
        $tradingPairsString = Setting::where('key', 'trading_pairs')->value('value');
        $this->tradingPairs = explode(',', $tradingPairsString);
    }

    public function loadAccountInfo(): void
    {
        try {
            // In production, fetch from Binance API
            $this->accountBalance = 10000; // Demo balance
            $this->usedMargin = Trade::where('status', 'OPEN')->sum('margin');
            $this->availableMargin = $this->accountBalance - $this->usedMargin;
        } catch (\Exception $e) {
            \Log::error('Account info load error: '.$e->getMessage());
        }
    }

    #[On('refresh-trading-panel')]
    public function updateCurrentPrice(): void
    {
        try {
            $binance = app(BinanceService::class);
            $this->currentPrice = $binance->getCurrentPrice($this->symbol);

            if ($this->orderType === 'MARKET') {
                $this->price = $this->currentPrice;
            }

            $this->calculatePosition();
        } catch (\Exception $e) {
            \Log::error('Price update error: '.$e->getMessage());
        }
    }

    public function loadRecentOrders(): void
    {
        $this->recentOrders = Trade::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($trade) {
                return [
                    'symbol' => $trade->symbol,
                    'side' => $trade->side,
                    'quantity' => $trade->quantity,
                    'price' => $trade->entry_price,
                    'status' => $trade->status,
                    'created_at' => $trade->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public function updatedSymbol(): void
    {
        $this->updateCurrentPrice();
    }

    public function updatedSide(): void
    {
        $this->calculatePosition();
    }

    public function updatedOrderType(): void
    {
        if ($this->orderType === 'MARKET') {
            $this->price = $this->currentPrice;
        }
        $this->calculatePosition();
    }

    public function updatedQuantity(): void
    {
        $this->calculatePosition();
    }

    public function updatedPrice(): void
    {
        $this->calculatePosition();
    }

    public function updatedStopLoss(): void
    {
        $this->calculatePosition();
    }

    public function updatedTakeProfit(): void
    {
        $this->calculatePosition();
    }

    public function updatedLeverage(): void
    {
        $this->calculatePosition();
    }

    public function updatedRiskPercentage(): void
    {
        $this->calculatePosition();
    }

    public function setQuickSize(int $percentage): void
    {
        $this->riskPercentage = $percentage / 10; // Convert to actual percentage
        $this->calculatePosition();
    }

    public function calculatePosition(): void
    {
        if ($this->quantity <= 0 || $this->price <= 0) {
            $this->resetCalculations();

            return;
        }

        $this->isCalculating = true;

        try {
            // Position value
            $this->positionValue = $this->quantity * $this->price;

            // Required margin
            $this->requiredMargin = $this->positionValue / $this->leverage;

            // Risk amount
            $this->riskAmount = ($this->accountBalance * $this->riskPercentage) / 100;

            // Calculate potential profit and loss
            if ($this->takeProfit > 0) {
                if ($this->side === 'BUY') {
                    $this->potentialProfit = ($this->takeProfit - $this->price) * $this->quantity;
                } else {
                    $this->potentialProfit = ($this->price - $this->takeProfit) * $this->quantity;
                }
            } else {
                $this->potentialProfit = 0;
            }

            if ($this->stopLoss > 0) {
                if ($this->side === 'BUY') {
                    $this->potentialLoss = ($this->price - $this->stopLoss) * $this->quantity;
                } else {
                    $this->potentialLoss = ($this->stopLoss - $this->price) * $this->quantity;
                }
            } else {
                $this->potentialLoss = 0;
            }

            // Risk/Reward ratio
            if ($this->potentialLoss > 0 && $this->potentialProfit > 0) {
                $this->riskRewardRatio = $this->potentialProfit / $this->potentialLoss;
            } else {
                $this->riskRewardRatio = 0;
            }
        } catch (\Exception $e) {
            \Log::error('Position calculation error: '.$e->getMessage());
        }

        $this->isCalculating = false;
    }

    private function resetCalculations(): void
    {
        $this->positionValue = 0;
        $this->requiredMargin = 0;
        $this->riskAmount = 0;
        $this->potentialProfit = 0;
        $this->potentialLoss = 0;
        $this->riskRewardRatio = 0;
    }

    public function calculateOptimalSize(): void
    {
        if ($this->stopLoss <= 0 || $this->price <= 0 || $this->riskPercentage <= 0) {
            $this->orderError = 'Please set Stop Loss, Price, and Risk Percentage first';

            return;
        }

        try {
            $riskManagement = app(RiskManagementService::class);

            // Calculate stop loss distance in price
            $stopLossDistance = abs($this->price - $this->stopLoss);

            // Calculate risk amount in dollars
            $riskAmountDollars = ($this->accountBalance * $this->riskPercentage) / 100;

            // Calculate optimal quantity
            $optimalQuantity = $riskAmountDollars / $stopLossDistance;

            // Apply leverage
            $optimalQuantity = $optimalQuantity * $this->leverage;

            $this->quantity = round($optimalQuantity, 4);
            $this->calculatePosition();

            $this->orderSuccess = 'Optimal position size calculated: '.$this->quantity;
            $this->orderError = '';
        } catch (\Exception $e) {
            $this->orderError = 'Error calculating optimal size: '.$e->getMessage();
            $this->orderSuccess = '';
        }
    }

    public function placeOrder(): void
    {
        $this->isSubmitting = true;
        $this->orderError = '';
        $this->orderSuccess = '';

        try {
            // Validate inputs
            if ($this->quantity <= 0) {
                throw new \Exception('Quantity must be greater than 0');
            }

            if ($this->price <= 0) {
                throw new \Exception('Price must be greater than 0');
            }

            if ($this->requiredMargin > $this->availableMargin) {
                throw new \Exception('Insufficient margin. Required: $'.number_format($this->requiredMargin, 2).', Available: $'.number_format($this->availableMargin, 2));
            }

            // Check risk management limits
            $maxPositions = Setting::where('key', 'max_positions')->value('value') ?? 5;
            $openPositions = Trade::where('status', 'OPEN')->count();

            if ($openPositions >= $maxPositions) {
                throw new \Exception('Maximum number of open positions reached ('.$maxPositions.')');
            }

            // In production, place order via Binance API
            // For now, create a trade record
            $trade = new Trade();
            $trade->symbol = $this->symbol;
            $trade->side = $this->side;
            $trade->order_type = $this->orderType;
            $trade->quantity = $this->quantity;
            $trade->entry_price = $this->price;
            $trade->leverage = $this->leverage;
            $trade->stop_loss = $this->stopLoss > 0 ? $this->stopLoss : null;
            $trade->take_profit = $this->takeProfit > 0 ? $this->takeProfit : null;
            $trade->margin = $this->requiredMargin;
            $trade->status = 'OPEN';
            $trade->save();

            $this->orderSuccess = 'Order placed successfully! Trade ID: '.$trade->id;
            $this->orderError = '';

            // Reset form
            $this->quantity = 0;
            $this->stopLoss = 0;
            $this->takeProfit = 0;
            $this->resetCalculations();

            // Reload account info and recent orders
            $this->loadAccountInfo();
            $this->loadRecentOrders();

            // Dispatch event to refresh positions panel
            $this->dispatch('refresh-positions');
        } catch (\Exception $e) {
            $this->orderError = $e->getMessage();
            $this->orderSuccess = '';
        }

        $this->isSubmitting = false;
    }

    public function quickBuy(): void
    {
        $this->side = 'BUY';
        $this->orderType = 'MARKET';
        $this->price = $this->currentPrice;
        $this->calculatePosition();
        $this->placeOrder();
    }

    public function quickSell(): void
    {
        $this->side = 'SELL';
        $this->orderType = 'MARKET';
        $this->price = $this->currentPrice;
        $this->calculatePosition();
        $this->placeOrder();
    }

    public function closeAllPositions(): void
    {
        try {
            $openTrades = Trade::where('status', 'OPEN')->get();

            foreach ($openTrades as $trade) {
                $trade->status = 'CLOSED';
                $trade->exit_price = $this->currentPrice;
                $trade->closed_at = now();

                // Calculate P&L
                if ($trade->side === 'BUY' || $trade->side === 'LONG') {
                    $trade->pnl = ($trade->exit_price - $trade->entry_price) * $trade->quantity;
                } else {
                    $trade->pnl = ($trade->entry_price - $trade->exit_price) * $trade->quantity;
                }

                $trade->save();
            }

            $this->orderSuccess = 'All positions closed successfully!';
            $this->loadAccountInfo();
            $this->loadRecentOrders();
            $this->dispatch('refresh-positions');
        } catch (\Exception $e) {
            $this->orderError = 'Error closing positions: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.trading-panel');
    }
}
