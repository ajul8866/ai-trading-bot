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
        $maxPositions = (int) Setting::where('key', 'max_positions')->value('value') ?? 5;
        $currentPositions = Trade::where('status', 'OPEN')->count();

        return $currentPositions < $maxPositions;
    }

    /**
     * Check if daily loss limit has been reached
     */
    public function isDailyLossLimitReached(): bool
    {
        $dailyLossLimit = (float) Setting::where('key', 'daily_loss_limit')->value('value') ?? 10;

        $todayLoss = Trade::where('status', 'CLOSED')
            ->whereDate('closed_at', Carbon::today())
            ->sum('pnl');

        // Get account balance from Binance
        $accountBalance = $this->binanceService->getAccountBalance();

        $lossPercentage = abs($todayLoss / $accountBalance) * 100;

        return $todayLoss < 0 && $lossPercentage >= $dailyLossLimit;
    }

    /**
     * Calculate position size based on risk per trade
     */
    public function calculatePositionSize(
        float $accountBalance,
        float $entryPrice,
        float $stopLoss,
        int $leverage = 1
    ): float {
        $riskPerTrade = (float) Setting::where('key', 'risk_per_trade')->value('value') ?? 2;

        // Amount willing to risk
        $riskAmount = ($accountBalance * $riskPerTrade) / 100;

        // Price distance to stop loss
        $priceRisk = abs($entryPrice - $stopLoss);

        if ($priceRisk == 0) {
            return 0;
        }

        // Calculate quantity
        $quantity = $riskAmount / $priceRisk;

        return round($quantity, 8);
    }

    /**
     * Validate if a trade meets risk criteria
     */
    public function validateTrade(
        float $entryPrice,
        ?float $stopLoss,
        ?float $takeProfit,
        int $leverage
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

        // Check risk/reward ratio
        if ($stopLoss && $takeProfit) {
            $risk = abs($entryPrice - $stopLoss);
            $reward = abs($takeProfit - $entryPrice);

            $riskRewardRatio = $risk > 0 ? $reward / $risk : 0;

            if ($riskRewardRatio < 1.5) {
                $errors[] = 'Risk/Reward ratio must be at least 1.5:1';
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

        if ($confidence >= 80) {
            $riskLevel = 'low';
        } elseif ($confidence < 70) {
            $riskLevel = 'high';
        }

        return [
            'risk_level' => $riskLevel,
            'confidence' => $confidence,
            'can_trade' => ! $this->isDailyLossLimitReached() && $this->canOpenPosition(),
            'daily_loss_limit_reached' => $this->isDailyLossLimitReached(),
            'max_positions_reached' => ! $this->canOpenPosition(),
        ];
    }
}
