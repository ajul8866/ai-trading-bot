<?php

namespace App\Jobs;

use App\Models\Trade;
use App\Services\BinanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MonitorPositionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(BinanceService $binanceService): void
    {
        try {
            Log::info('Monitoring open positions');

            // Get all open trades
            $openTrades = Trade::where('status', 'OPEN')->get();

            if ($openTrades->isEmpty()) {
                Log::info('No open positions to monitor');

                return;
            }

            Log::info('Found open positions', ['count' => $openTrades->count()]);

            foreach ($openTrades as $trade) {
                try {
                    $this->monitorTrade($trade, $binanceService);
                } catch (\Exception $e) {
                    Log::error('Error monitoring trade', [
                        'trade_id' => $trade->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in position monitoring', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function monitorTrade(Trade $trade, BinanceService $binanceService): void
    {
        $currentPrice = $binanceService->getCurrentPrice($trade->symbol);

        if ($currentPrice <= 0) {
            Log::warning('Invalid current price', ['symbol' => $trade->symbol]);

            return;
        }

        Log::info('Monitoring trade', [
            'trade_id' => $trade->id,
            'symbol' => $trade->symbol,
            'side' => $trade->side,
            'entry_price' => $trade->entry_price,
            'current_price' => $currentPrice,
            'stop_loss' => $trade->stop_loss,
            'take_profit' => $trade->take_profit,
        ]);

        $shouldClose = false;
        $closeReason = '';

        // Check stop loss and take profit
        if ($trade->side === 'LONG') {
            // For LONG: close if price <= stop loss or price >= take profit
            if ($trade->stop_loss && $currentPrice <= $trade->stop_loss) {
                $shouldClose = true;
                $closeReason = 'Stop Loss Hit';
            } elseif ($trade->take_profit && $currentPrice >= $trade->take_profit) {
                $shouldClose = true;
                $closeReason = 'Take Profit Hit';
            }
        } else {
            // For SHORT: close if price >= stop loss or price <= take profit
            if ($trade->stop_loss && $currentPrice >= $trade->stop_loss) {
                $shouldClose = true;
                $closeReason = 'Stop Loss Hit';
            } elseif ($trade->take_profit && $currentPrice <= $trade->take_profit) {
                $shouldClose = true;
                $closeReason = 'Take Profit Hit';
            }
        }

        if ($shouldClose) {
            Log::info('Closing position', [
                'trade_id' => $trade->id,
                'reason' => $closeReason,
                'current_price' => $currentPrice,
            ]);

            $this->closeTrade($trade, $binanceService, $currentPrice, $closeReason);
        }
    }

    private function closeTrade(
        Trade $trade,
        BinanceService $binanceService,
        float $currentPrice,
        string $reason
    ): void {
        try {
            // Close position on Binance
            $closeResult = $binanceService->closePosition(
                $trade->symbol,
                $trade->quantity,
                $trade->side
            );

            if (isset($closeResult['error'])) {
                Log::error('Failed to close position on Binance', [
                    'trade_id' => $trade->id,
                    'error' => $closeResult['error'],
                ]);

                return;
            }

            // Calculate PnL
            $pnl = $this->calculatePnL($trade, $currentPrice);
            $pnlPercentage = (($currentPrice - $trade->entry_price) / $trade->entry_price) * 100;

            if ($trade->side === 'SHORT') {
                $pnlPercentage = -$pnlPercentage;
            }

            // Update trade in database
            $trade->update([
                'status' => 'CLOSED',
                'exit_price' => $currentPrice,
                'pnl' => $pnl,
                'pnl_percentage' => $pnlPercentage,
                'closed_at' => now(),
            ]);

            Log::info('Position closed successfully', [
                'trade_id' => $trade->id,
                'reason' => $reason,
                'entry_price' => $trade->entry_price,
                'exit_price' => $currentPrice,
                'pnl' => $pnl,
                'pnl_percentage' => $pnlPercentage,
            ]);
        } catch (\Exception $e) {
            Log::error('Error closing trade', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function calculatePnL(Trade $trade, float $exitPrice): float
    {
        $priceDifference = $exitPrice - $trade->entry_price;

        if ($trade->side === 'SHORT') {
            $priceDifference = -$priceDifference;
        }

        $pnl = $priceDifference * $trade->quantity * $trade->leverage;

        return round($pnl, 2);
    }
}
