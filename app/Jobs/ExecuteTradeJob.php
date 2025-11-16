<?php

namespace App\Jobs;

use App\Models\AiDecision;
use App\Models\Trade;
use App\Services\BinanceService;
use App\Services\RiskManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecuteTradeJob implements ShouldQueue
{
    use Queueable;

    /**
     * CRITICAL FIX: Add retry configuration for trade execution reliability
     */
    public int $tries = 3; // Retry up to 3 times for failed trade execution
    public int $timeout = 60;

    /**
     * Exponential backoff for retries (seconds)
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait longer between trade retries (30s, 60s, 120s)
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $aiDecisionId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        BinanceService $binanceService,
        RiskManagementService $riskService
    ): void {
        try {
            // Load AI decision
            $aiDecision = AiDecision::find($this->aiDecisionId);

            if (! $aiDecision) {
                Log::error('AI decision not found', ['id' => $this->aiDecisionId]);

                return;
            }

            if ($aiDecision->executed) {
                Log::info('AI decision already executed', ['id' => $this->aiDecisionId]);

                return;
            }

            // CRITICAL: Idempotency check - prevent duplicate orders on retry
            $existingTrade = Trade::where('ai_decision_id', $this->aiDecisionId)->first();
            if ($existingTrade) {
                Log::warning('Trade already exists for this AI decision', [
                    'ai_decision_id' => $this->aiDecisionId,
                    'trade_id' => $existingTrade->id,
                    'status' => $existingTrade->status,
                ]);

                // Mark as executed if not already marked
                if (!$aiDecision->executed) {
                    $aiDecision->update(['executed' => true]);
                }

                return;
            }

            Log::info('Executing trade', [
                'ai_decision_id' => $this->aiDecisionId,
                'symbol' => $aiDecision->symbol,
                'decision' => $aiDecision->decision,
            ]);

            // Use database transaction for consistency
            DB::beginTransaction();

            try {
                // Final safety checks
                if (! $riskService->canOpenPosition()) {
                    throw new \Exception('Cannot open position: max positions reached');
                }

                if ($riskService->isDailyLossLimitReached()) {
                    throw new \Exception('Cannot open position: daily loss limit reached');
                }

                // Validate trade parameters
                $validation = $riskService->validateTrade(
                    $binanceService->getCurrentPrice($aiDecision->symbol),
                    $aiDecision->recommended_stop_loss,
                    $aiDecision->recommended_take_profit,
                    $aiDecision->recommended_leverage ?? 1,
                    $aiDecision->decision // Pass the actual decision (BUY/SELL)
                );

                if (! $validation['valid']) {
                    throw new \Exception('Trade validation failed: '.implode(', ', $validation['errors']));
                }

                // Calculate position size
                $currentPrice = $binanceService->getCurrentPrice($aiDecision->symbol);
                $accountBalance = $binanceService->getAccountBalance();

                // Use configured default leverage instead of AI recommendation
                $leverage = (int) \App\Models\Setting::getValue('default_leverage', 10);

                $quantity = $riskService->calculatePositionSize(
                    $accountBalance,
                    $currentPrice,
                    $aiDecision->recommended_stop_loss,
                    $leverage
                );

                if ($quantity <= 0) {
                    throw new \Exception('Invalid position size calculated');
                }

                // Adjust quantity precision based on symbol
                $quantity = $this->adjustQuantityPrecision($aiDecision->symbol, $quantity);

                // Execute the order on Binance
                $orderSide = $aiDecision->decision === 'BUY' ? 'BUY' : 'SELL';
                $tradeSide = $aiDecision->decision === 'BUY' ? 'LONG' : 'SHORT';

                Log::info('Placing order on Binance', [
                    'symbol' => $aiDecision->symbol,
                    'side' => $orderSide,
                    'quantity' => $quantity,
                    'leverage' => $leverage,
                ]);

                $orderResult = $binanceService->placeMarketOrder(
                    $aiDecision->symbol,
                    $orderSide,
                    $quantity,
                    $leverage
                );

                if (isset($orderResult['error'])) {
                    throw new \Exception('Order failed: '.$orderResult['error']);
                }

                // Store trade in database
                $trade = Trade::create([
                    'symbol' => $aiDecision->symbol,
                    'side' => $tradeSide,
                    'entry_price' => $currentPrice,
                    'quantity' => $quantity,
                    'leverage' => $leverage,
                    'stop_loss' => $aiDecision->recommended_stop_loss,
                    'take_profit' => $aiDecision->recommended_take_profit,
                    'status' => 'OPEN',
                    'binance_order_id' => $orderResult['orderId'] ?? null,
                    'ai_decision_id' => $aiDecision->id,
                    'opened_at' => now(),
                ]);

                // Mark AI decision as executed
                $aiDecision->update(['executed' => true]);

                DB::commit();

                Log::info('Trade executed successfully', [
                    'trade_id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'side' => $trade->side,
                    'entry_price' => $trade->entry_price,
                    'quantity' => $trade->quantity,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                // Log execution error in AI decision
                $aiDecision->update([
                    'execution_error' => $e->getMessage(),
                ]);

                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error executing trade', [
                'ai_decision_id' => $this->aiDecisionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Adjust quantity precision based on symbol requirements
     */
    private function adjustQuantityPrecision(string $symbol, float $quantity): float
    {
        // Define precision for common symbols (Binance Futures requirements)
        $precisionMap = [
            'BTCUSDT' => 3,
            'ETHUSDT' => 3,
            'BNBUSDT' => 2,
            'ADAUSDT' => 0,
            'SOLUSDT' => 0,
            'XRPUSDT' => 1,
            'DOTUSDT' => 1,
            'DOGEUSDT' => 0,
            'MATICUSDT' => 0,
            'LTCUSDT' => 3,
            'AVAXUSDT' => 1,
            'LINKUSDT' => 2,
            'ATOMUSDT' => 2,
            'NEARUSDT' => 0,
            'APTUSDT' => 1,
        ];

        $precision = $precisionMap[$symbol] ?? 3; // Default to 3 decimals

        return round($quantity, $precision);
    }
}
