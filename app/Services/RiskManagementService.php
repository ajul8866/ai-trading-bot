<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Trade;
use Carbon\Carbon;

class RiskManagementService
{
    public function __construct(
        private BinanceService $binanceService
    ) {}

    /**
     * Check if we can open a new position
     */
    public function canOpenPosition(): bool
    {
        $maxPositions = (int) Setting::getValue('max_positions', 5);
        $currentPositions = Trade::where('status', 'OPEN')->count();

        return $currentPositions < $maxPositions;
    }

    /**
     * Check if daily loss limit has been reached
     */
    public function isDailyLossLimitReached(): bool
    {
        $dailyLossLimit = (float) Setting::getValue('daily_loss_limit', 10);

        $todayLoss = Trade::where('status', 'CLOSED')
            ->whereDate('closed_at', Carbon::today())
            ->sum('pnl');

        // Get account balance from Binance
        try {
            $accountBalance = $this->binanceService->getAccountBalance();
        } catch (\Exception $e) {
            // CRITICAL FIX: Don't assume limit reached on error - throw exception to halt trading
            \Log::critical('Failed to check daily loss limit - cannot verify risk management', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Cannot verify daily loss limit - halting trading for safety: ' . $e->getMessage());
        }

        // If account balance is 0, cannot trade (limit reached)
        if ($accountBalance <= 0) {
            return true;
        }

        // If there's no loss, return false
        if ($todayLoss >= 0) {
            return false;
        }

        // Calculate loss percentage against current balance
        $lossPercentage = abs($todayLoss / $accountBalance) * 100;

        return $lossPercentage >= $dailyLossLimit;
    }

    /**
     * Calculate position size based on percentage of available balance
     * Scales automatically with balance - no hardcoded values
     */
    public function calculatePositionSize(
        float $accountBalance,
        float $entryPrice,
        float $stopLoss,
        int $leverage = 1
    ): float {
        // Use risk_per_trade as percentage of balance to allocate per position
        $balancePercentage = (float) Setting::getValue('risk_per_trade', 2);

        // Calculate notional value: balance * percentage * leverage
        // Example: $100 * 50% * 5x = $250 notional
        $notionalValue = ($accountBalance * $balancePercentage / 100) * $leverage;

        if ($entryPrice <= 0) {
            return 0;
        }

        // Quantity = Notional / Price (auto scales with balance)
        $quantity = $notionalValue / $entryPrice;

        return round($quantity, 8);
    }

    /**
     * Validate if a trade meets risk criteria
     */
    public function validateTrade(
        float $entryPrice,
        ?float $stopLoss,
        ?float $takeProfit,
        int $leverage,
        string $side = 'BUY'
    ): array {
        $errors = [];

        // Check if stop loss is set
        if (! $stopLoss) {
            $errors[] = 'Stop loss must be set';
        }

        // Check if take profit is set
        if (! $takeProfit) {
            $errors[] = 'Take profit must be set';
        }

        // Validate leverage
        if ($leverage < 1 || $leverage > 10) {
            $errors[] = 'Leverage must be between 1 and 10';
        }

        // Validate stop loss and take profit direction
        if ($stopLoss && $takeProfit) {
            if (in_array($side, ['BUY', 'LONG'])) {
                // For LONG: SL < entry < TP
                if ($stopLoss >= $entryPrice) {
                    $errors[] = 'For BUY/LONG: Stop loss must be below entry price';
                }
                if ($takeProfit <= $entryPrice) {
                    $errors[] = 'For BUY/LONG: Take profit must be above entry price';
                }
            } elseif (in_array($side, ['SELL', 'SHORT'])) {
                // For SHORT: TP < entry < SL
                if ($stopLoss <= $entryPrice) {
                    $errors[] = 'For SELL/SHORT: Stop loss must be above entry price';
                }
                if ($takeProfit >= $entryPrice) {
                    $errors[] = 'For SELL/SHORT: Take profit must be below entry price';
                }
            }

            // Check risk/reward ratio
            $risk = abs($entryPrice - $stopLoss);
            $reward = abs($takeProfit - $entryPrice);

            $riskRewardRatio = $risk > 0 ? $reward / $risk : 0;

            if ($riskRewardRatio < 1.0) {
                $errors[] = 'Risk/Reward ratio must be at least 1.0:1';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get risk assessment for AI decision
     */
    public function assessRisk(
        string $symbol,
        float $confidence,
        array $marketConditions
    ): array {
        $riskLevel = 'medium';

        // Base risk level on confidence
        if ($confidence >= 80) {
            $riskLevel = 'low';
        } elseif ($confidence < 70) {
            $riskLevel = 'high';
        }

        // Adjust risk based on market conditions
        if (isset($marketConditions['volatility'])) {
            $volatility = $marketConditions['volatility'];
            if ($volatility === 'high' && $riskLevel === 'low') {
                $riskLevel = 'medium'; // Upgrade risk in high volatility
            } elseif ($volatility === 'high' && $riskLevel === 'medium') {
                $riskLevel = 'high'; // Further upgrade
            }
        }

        // Adjust risk based on market trend strength
        if (isset($marketConditions['strength'])) {
            $strength = $marketConditions['strength'];
            if ($strength === 'weak' && $riskLevel === 'low') {
                $riskLevel = 'medium'; // Weak trends are riskier
            }
        }

        return [
            'risk_level' => $riskLevel,
            'confidence' => $confidence,
            'market_volatility' => $marketConditions['volatility'] ?? 'unknown',
            'market_trend' => $marketConditions['trend'] ?? 'unknown',
            'market_strength' => $marketConditions['strength'] ?? 'unknown',
            'can_trade' => ! $this->isDailyLossLimitReached() && $this->canOpenPosition(),
            'daily_loss_limit_reached' => $this->isDailyLossLimitReached(),
            'max_positions_reached' => ! $this->canOpenPosition(),
        ];
    }
}
