<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Trade;

/**
 * Portfolio Management Service
 *
 * Comprehensive portfolio management system that handles:
 * - Position tracking and monitoring
 * - Risk allocation across multiple positions
 * - Correlation analysis between trading pairs
 * - Portfolio optimization and rebalancing
 * - Exposure management
 * - Drawdown tracking and risk limits
 *
 * Key Features:
 * - Real-time portfolio valuation
 * - Risk-adjusted position sizing
 * - Diversification monitoring
 * - Correlation-based risk management
 * - Maximum exposure limits per pair
 * - Sector/category exposure tracking
 * - Portfolio performance metrics
 * - Risk-adjusted returns (Sharpe, Sortino)
 * - Maximum drawdown tracking
 * - Value at Risk (VaR) calculation
 */
class PortfolioManagementService
{
    private RiskManagementService $riskManagement;

    // Portfolio limits
    private int $maxConcurrentPositions = 5;

    private float $maxPortfolioRisk = 0.10; // 10% max total portfolio risk

    private float $maxSinglePairExposure = 0.30; // 30% max per pair

    private float $maxCorrelatedExposure = 0.50; // 50% max in correlated pairs

    private float $correlationThreshold = 0.7; // Pairs with >0.7 correlation considered correlated

    // Risk metrics thresholds
    private float $maxDrawdown = 0.20; // 20% max drawdown before action

    private float $targetSharpeRatio = 1.5; // Target Sharpe ratio

    private float $maxVaR95 = 0.05; // 5% Value at Risk (95% confidence)

    public function __construct(RiskManagementService $riskManagement)
    {
        $this->riskManagement = $riskManagement;
    }

    /**
     * Get current portfolio snapshot
     */
    public function getPortfolioSnapshot(): array
    {
        $openPositions = $this->getOpenPositions();
        $accountBalance = $this->getAccountBalance();

        $totalValue = $accountBalance;
        $totalUnrealizedPnL = 0;
        $totalExposure = 0;
        $positionsByPair = [];
        $positionsBySide = ['LONG' => 0, 'SHORT' => 0];

        foreach ($openPositions as $position) {
            $unrealizedPnL = $this->calculateUnrealizedPnL($position);
            $totalUnrealizedPnL += $unrealizedPnL;

            $exposure = abs($position->entry_price * $position->quantity);
            $totalExposure += $exposure;

            // Group by pair
            if (! isset($positionsByPair[$position->symbol])) {
                $positionsByPair[$position->symbol] = [
                    'count' => 0,
                    'exposure' => 0,
                    'pnl' => 0,
                    'positions' => [],
                ];
            }

            $positionsByPair[$position->symbol]['count']++;
            $positionsByPair[$position->symbol]['exposure'] += $exposure;
            $positionsByPair[$position->symbol]['pnl'] += $unrealizedPnL;
            $positionsByPair[$position->symbol]['positions'][] = $position;

            // Count by side
            $positionsBySide[$position->side]++;
        }

        $totalValue += $totalUnrealizedPnL;
        $exposureRatio = $accountBalance > 0 ? $totalExposure / $accountBalance : 0;

        return [
            'account_balance' => $accountBalance,
            'total_value' => $totalValue,
            'unrealized_pnl' => $totalUnrealizedPnL,
            'total_exposure' => $totalExposure,
            'exposure_ratio' => round($exposureRatio, 2),
            'open_positions_count' => count($openPositions),
            'positions_by_pair' => $positionsByPair,
            'positions_by_side' => $positionsBySide,
            'available_capital' => $accountBalance - $totalExposure,
            'leverage_used' => round($exposureRatio, 2),
            'timestamp' => now(),
        ];
    }

    /**
     * Calculate portfolio risk metrics
     */
    public function calculateRiskMetrics(): array
    {
        $openPositions = $this->getOpenPositions();
        $accountBalance = $this->getAccountBalance();
        $closedTrades = $this->getClosedTrades(30); // Last 30 days

        // Calculate current risk
        $totalRisk = 0;
        $riskByPair = [];

        foreach ($openPositions as $position) {
            $positionRisk = $this->calculatePositionRisk($position);
            $totalRisk += $positionRisk;

            if (! isset($riskByPair[$position->symbol])) {
                $riskByPair[$position->symbol] = 0;
            }
            $riskByPair[$position->symbol] += $positionRisk;
        }

        $portfolioRiskRatio = $accountBalance > 0 ? $totalRisk / $accountBalance : 0;

        // Calculate historical metrics
        $returns = $this->calculateDailyReturns($closedTrades);
        $sharpeRatio = $this->calculateSharpeRatio($returns);
        $sortinoRatio = $this->calculateSortinoRatio($returns);
        $maxDrawdown = $this->calculateMaxDrawdown();
        $var95 = $this->calculateVaR($returns, 0.95);

        // Calculate correlation matrix
        $correlationMatrix = $this->calculateCorrelationMatrix();

        // Assess diversification
        $diversificationScore = $this->calculateDiversificationScore($riskByPair, $correlationMatrix);

        return [
            'total_risk' => $totalRisk,
            'portfolio_risk_ratio' => round($portfolioRiskRatio, 4),
            'risk_by_pair' => $riskByPair,
            'sharpe_ratio' => round($sharpeRatio, 2),
            'sortino_ratio' => round($sortinoRatio, 2),
            'max_drawdown' => round($maxDrawdown, 4),
            'current_drawdown' => round($this->getCurrentDrawdown(), 4),
            'value_at_risk_95' => round($var95, 4),
            'diversification_score' => round($diversificationScore, 2),
            'correlation_matrix' => $correlationMatrix,
            'risk_status' => $this->assessRiskStatus($portfolioRiskRatio, $maxDrawdown),
        ];
    }

    /**
     * Check if can open new position
     */
    public function canOpenPosition(string $symbol, string $side, float $positionSize, float $entryPrice): array
    {
        $reasons = [];
        $canOpen = true;

        // Check max concurrent positions
        $openPositions = $this->getOpenPositions();
        if (count($openPositions) >= $this->maxConcurrentPositions) {
            $canOpen = false;
            $reasons[] = "Maximum concurrent positions reached ({$this->maxConcurrentPositions})";
        }

        // Check pair exposure
        $pairExposure = $this->getPairExposure($symbol);
        $newExposure = $positionSize * $entryPrice;
        $accountBalance = $this->getAccountBalance();
        $totalPairExposure = $pairExposure + $newExposure;
        $pairExposureRatio = $totalPairExposure / $accountBalance;

        if ($pairExposureRatio > $this->maxSinglePairExposure) {
            $canOpen = false;
            $reasons[] = "Pair exposure limit exceeded for {$symbol} (".round($pairExposureRatio * 100, 2).'% > '.($this->maxSinglePairExposure * 100).'%)';
        }

        // Check portfolio risk
        $currentRisk = $this->calculatePortfolioRisk();
        $newPositionRisk = $this->estimatePositionRisk($positionSize, $entryPrice);
        $totalRisk = $currentRisk + $newPositionRisk;
        $riskRatio = $totalRisk / $accountBalance;

        if ($riskRatio > $this->maxPortfolioRisk) {
            $canOpen = false;
            $reasons[] = 'Portfolio risk limit exceeded ('.round($riskRatio * 100, 2).'% > '.($this->maxPortfolioRisk * 100).'%)';
        }

        // Check correlation exposure
        $correlatedPairs = $this->getCorrelatedPairs($symbol);
        $correlatedExposure = $this->getCorrelatedExposure($symbol, $correlatedPairs);
        $totalCorrelatedExposure = $correlatedExposure + $newExposure;
        $correlatedRatio = $totalCorrelatedExposure / $accountBalance;

        if ($correlatedRatio > $this->maxCorrelatedExposure) {
            $canOpen = false;
            $reasons[] = 'Correlated exposure limit exceeded ('.round($correlatedRatio * 100, 2).'% > '.($this->maxCorrelatedExposure * 100).'%)';
        }

        // Check drawdown
        $currentDrawdown = $this->getCurrentDrawdown();
        if ($currentDrawdown > $this->maxDrawdown) {
            $canOpen = false;
            $reasons[] = 'Maximum drawdown exceeded ('.round($currentDrawdown * 100, 2).'% > '.($this->maxDrawdown * 100).'%)';
        }

        // Check daily loss limit
        $dailyPnL = $this->getDailyPnL();
        $dailyLossLimit = Setting::getValue('daily_loss_limit', 0.1) * $accountBalance;
        if ($dailyPnL < -$dailyLossLimit) {
            $canOpen = false;
            $reasons[] = 'Daily loss limit reached';
        }

        if ($canOpen) {
            $reasons[] = 'All checks passed';
        }

        return [
            'can_open' => $canOpen,
            'reasons' => $reasons,
            'current_positions' => count($openPositions),
            'pair_exposure_ratio' => round($pairExposureRatio, 4),
            'portfolio_risk_ratio' => round($riskRatio, 4),
            'correlated_exposure_ratio' => round($correlatedRatio, 4),
            'current_drawdown' => round($currentDrawdown, 4),
        ];
    }

    /**
     * Suggest position size based on portfolio risk
     */
    public function suggestPositionSize(string $symbol, float $entryPrice, float $stopLoss, string $side): float
    {
        $accountBalance = $this->getAccountBalance();

        // Calculate available risk budget
        $currentRisk = $this->calculatePortfolioRisk();
        $availableRisk = ($this->maxPortfolioRisk * $accountBalance) - $currentRisk;

        // Calculate position size based on risk per trade
        $riskPerTrade = min($availableRisk, $accountBalance * 0.02); // Max 2% per trade
        $stopDistance = abs($entryPrice - $stopLoss);

        if ($stopDistance <= 0) {
            return 0;
        }

        $baseSuggestion = $riskPerTrade / $stopDistance;

        // Adjust for pair exposure
        $pairExposure = $this->getPairExposure($symbol);
        $maxPairExposure = $accountBalance * $this->maxSinglePairExposure;
        $availablePairExposure = $maxPairExposure - $pairExposure;
        $maxSizeByPair = $availablePairExposure / $entryPrice;

        // Adjust for correlation
        $correlatedPairs = $this->getCorrelatedPairs($symbol);
        $correlatedExposure = $this->getCorrelatedExposure($symbol, $correlatedPairs);
        $maxCorrelatedExposure = $accountBalance * $this->maxCorrelatedExposure;
        $availableCorrelatedExposure = $maxCorrelatedExposure - $correlatedExposure;
        $maxSizeByCorrelation = $availableCorrelatedExposure / $entryPrice;

        // Take the minimum
        $suggestedSize = min($baseSuggestion, $maxSizeByPair, $maxSizeByCorrelation);

        return max(0, round($suggestedSize, 8));
    }

    /**
     * Get portfolio optimization suggestions
     */
    public function getOptimizationSuggestions(): array
    {
        $suggestions = [];
        $openPositions = $this->getOpenPositions();
        $snapshot = $this->getPortfolioSnapshot();
        $riskMetrics = $this->calculateRiskMetrics();

        // Check for over-concentration
        foreach ($snapshot['positions_by_pair'] as $symbol => $data) {
            $exposureRatio = $data['exposure'] / $snapshot['account_balance'];
            if ($exposureRatio > $this->maxSinglePairExposure) {
                $suggestions[] = [
                    'type' => 'REDUCE_EXPOSURE',
                    'priority' => 'HIGH',
                    'symbol' => $symbol,
                    'message' => "Reduce exposure in {$symbol} (currently ".round($exposureRatio * 100, 2).'%)',
                    'target_exposure' => $this->maxSinglePairExposure * $snapshot['account_balance'],
                ];
            }
        }

        // Check for poor diversification
        if ($riskMetrics['diversification_score'] < 0.5) {
            $suggestions[] = [
                'type' => 'IMPROVE_DIVERSIFICATION',
                'priority' => 'MEDIUM',
                'message' => 'Portfolio is under-diversified (score: '.round($riskMetrics['diversification_score'], 2).')',
                'recommendation' => 'Consider spreading risk across more uncorrelated pairs',
            ];
        }

        // Check for excessive risk
        if ($riskMetrics['portfolio_risk_ratio'] > $this->maxPortfolioRisk * 0.8) {
            $suggestions[] = [
                'type' => 'REDUCE_RISK',
                'priority' => 'HIGH',
                'message' => 'Portfolio risk approaching limit ('.round($riskMetrics['portfolio_risk_ratio'] * 100, 2).'%)',
                'recommendation' => 'Reduce position sizes or close losing positions',
            ];
        }

        // Check for drawdown
        if ($riskMetrics['current_drawdown'] > $this->maxDrawdown * 0.7) {
            $suggestions[] = [
                'type' => 'DRAWDOWN_WARNING',
                'priority' => 'HIGH',
                'message' => 'Approaching maximum drawdown ('.round($riskMetrics['current_drawdown'] * 100, 2).'%)',
                'recommendation' => 'Consider pausing trading or reducing position sizes',
            ];
        }

        // Check for low Sharpe ratio
        if ($riskMetrics['sharpe_ratio'] < 1.0) {
            $suggestions[] = [
                'type' => 'LOW_RISK_ADJUSTED_RETURNS',
                'priority' => 'MEDIUM',
                'message' => 'Sharpe ratio below target ('.round($riskMetrics['sharpe_ratio'], 2).' < 1.0)',
                'recommendation' => 'Review and optimize trading strategies',
            ];
        }

        // Check for correlated positions
        $correlatedGroups = $this->findCorrelatedPositionGroups();
        if (count($correlatedGroups) > 0) {
            foreach ($correlatedGroups as $group) {
                if ($group['exposure_ratio'] > $this->maxCorrelatedExposure) {
                    $suggestions[] = [
                        'type' => 'CORRELATED_EXPOSURE',
                        'priority' => 'MEDIUM',
                        'message' => 'High correlation between '.implode(', ', $group['symbols']),
                        'correlation' => round($group['correlation'], 2),
                        'recommendation' => 'Consider closing some correlated positions',
                    ];
                }
            }
        }

        // Check for long/short balance
        $longExposure = 0;
        $shortExposure = 0;
        foreach ($openPositions as $position) {
            $exposure = abs($position->entry_price * $position->quantity);
            if ($position->side === 'LONG') {
                $longExposure += $exposure;
            } else {
                $shortExposure += $exposure;
            }
        }

        $totalExposure = $longExposure + $shortExposure;
        if ($totalExposure > 0) {
            $longRatio = $longExposure / $totalExposure;
            if ($longRatio > 0.8 || $longRatio < 0.2) {
                $suggestions[] = [
                    'type' => 'DIRECTIONAL_BIAS',
                    'priority' => 'LOW',
                    'message' => 'Portfolio has directional bias ('.round($longRatio * 100, 2).'% long)',
                    'recommendation' => 'Consider adding positions in opposite direction for balance',
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Calculate position correlation matrix
     */
    public function calculateCorrelationMatrix(): array
    {
        // Get all trading pairs
        $pairs = $this->getActivePairs();
        $matrix = [];

        // For each pair combination, calculate correlation
        foreach ($pairs as $i => $pair1) {
            $matrix[$pair1] = [];
            foreach ($pairs as $j => $pair2) {
                if ($i === $j) {
                    $matrix[$pair1][$pair2] = 1.0;
                } else {
                    // Calculate correlation between pair1 and pair2
                    $correlation = $this->calculatePairCorrelation($pair1, $pair2);
                    $matrix[$pair1][$pair2] = $correlation;
                }
            }
        }

        return $matrix;
    }

    /**
     * Calculate correlation between two trading pairs
     */
    private function calculatePairCorrelation(string $pair1, string $pair2): float
    {
        // Get historical price data for both pairs (last 30 days)
        // This is simplified - in production, you'd fetch actual price data

        // For now, return estimated correlation based on pair similarity
        // BTC pairs tend to correlate, ETH pairs correlate, etc.

        $base1 = $this->extractBaseCurrency($pair1);
        $base2 = $this->extractBaseCurrency($pair2);

        if ($base1 === $base2) {
            return 1.0; // Same pair
        }

        // Major crypto correlations (simplified)
        $highCorrelation = [
            ['BTC', 'ETH'] => 0.75,
            ['BTC', 'BNB'] => 0.70,
            ['ETH', 'BNB'] => 0.72,
        ];

        foreach ($highCorrelation as $pair => $corr) {
            if (($pair[0] === $base1 && $pair[1] === $base2) || ($pair[0] === $base2 && $pair[1] === $base1)) {
                return $corr;
            }
        }

        // Default low correlation
        return 0.3;
    }

    /**
     * Extract base currency from symbol (e.g., BTCUSDT -> BTC)
     */
    private function extractBaseCurrency(string $symbol): string
    {
        return str_replace('USDT', '', $symbol);
    }

    /**
     * Calculate diversification score (0-1, higher is better)
     */
    private function calculateDiversificationScore(array $riskByPair, array $correlationMatrix): float
    {
        if (empty($riskByPair)) {
            return 1.0;
        }

        // Calculate Herfindahl index for concentration
        $totalRisk = array_sum($riskByPair);
        $concentrationIndex = 0;

        foreach ($riskByPair as $risk) {
            $weight = $totalRisk > 0 ? $risk / $totalRisk : 0;
            $concentrationIndex += $weight * $weight;
        }

        // 1 - HHI gives diversification (0 = concentrated, 1 = diversified)
        $diversificationBase = 1 - $concentrationIndex;

        // Adjust for correlation
        $avgCorrelation = $this->calculateAverageCorrelation($correlationMatrix);
        $correlationPenalty = $avgCorrelation * 0.5; // High correlation reduces diversification

        $score = max(0, $diversificationBase - $correlationPenalty);

        return min(1.0, $score);
    }

    /**
     * Calculate average correlation across all pairs
     */
    private function calculateAverageCorrelation(array $correlationMatrix): float
    {
        if (empty($correlationMatrix)) {
            return 0;
        }

        $totalCorr = 0;
        $count = 0;

        foreach ($correlationMatrix as $pair1 => $correlations) {
            foreach ($correlations as $pair2 => $corr) {
                if ($pair1 !== $pair2) { // Exclude self-correlation
                    $totalCorr += abs($corr);
                    $count++;
                }
            }
        }

        return $count > 0 ? $totalCorr / $count : 0;
    }

    /**
     * Find groups of correlated positions
     */
    private function findCorrelatedPositionGroups(): array
    {
        $openPositions = $this->getOpenPositions();
        $correlationMatrix = $this->calculateCorrelationMatrix();
        $accountBalance = $this->getAccountBalance();
        $groups = [];

        $processed = [];

        foreach ($openPositions as $position1) {
            if (in_array($position1->symbol, $processed)) {
                continue;
            }

            $group = [
                'symbols' => [$position1->symbol],
                'exposure' => abs($position1->entry_price * $position1->quantity),
                'max_correlation' => 0,
            ];

            foreach ($openPositions as $position2) {
                if ($position1->symbol === $position2->symbol) {
                    continue;
                }

                $correlation = $correlationMatrix[$position1->symbol][$position2->symbol] ?? 0;

                if ($correlation > $this->correlationThreshold) {
                    $group['symbols'][] = $position2->symbol;
                    $group['exposure'] += abs($position2->entry_price * $position2->quantity);
                    $group['max_correlation'] = max($group['max_correlation'], $correlation);
                }
            }

            if (count($group['symbols']) > 1) {
                $group['exposure_ratio'] = $group['exposure'] / $accountBalance;
                $group['correlation'] = $group['max_correlation'];
                $groups[] = $group;
                $processed = array_merge($processed, $group['symbols']);
            }
        }

        return $groups;
    }

    /**
     * Calculate Sharpe Ratio
     */
    private function calculateSharpeRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }

        $avgReturn = array_sum($returns) / count($returns);

        // Calculate standard deviation
        $squaredDiffs = array_map(function ($r) use ($avgReturn) {
            return pow($r - $avgReturn, 2);
        }, $returns);

        $variance = array_sum($squaredDiffs) / count($returns);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return 0;
        }

        // Risk-free rate assumed to be 0 for crypto
        return ($avgReturn - 0) / $stdDev;
    }

    /**
     * Calculate Sortino Ratio (only penalizes downside volatility)
     */
    private function calculateSortinoRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0;
        }

        $avgReturn = array_sum($returns) / count($returns);

        // Calculate downside deviation (only negative returns)
        $negativeReturns = array_filter($returns, function ($r) {
            return $r < 0;
        });

        if (empty($negativeReturns)) {
            return $avgReturn > 0 ? 999 : 0; // Very high if positive returns, no downside
        }

        $squaredDownsideDiffs = array_map(function ($r) {
            return pow($r, 2);
        }, $negativeReturns);

        $downsideVariance = array_sum($squaredDownsideDiffs) / count($returns);
        $downsideStdDev = sqrt($downsideVariance);

        if ($downsideStdDev == 0) {
            return 0;
        }

        return ($avgReturn - 0) / $downsideStdDev;
    }

    /**
     * Calculate Value at Risk (VaR)
     */
    private function calculateVaR(array $returns, float $confidence): float
    {
        if (empty($returns)) {
            return 0;
        }

        // Sort returns
        sort($returns);

        // Find the percentile
        $index = (int) floor((1 - $confidence) * count($returns));
        $index = max(0, min($index, count($returns) - 1));

        return abs($returns[$index]);
    }

    /**
     * Calculate maximum drawdown
     */
    private function calculateMaxDrawdown(): float
    {
        // Get account balance history
        $trades = Trade::orderBy('created_at', 'asc')->get();

        if ($trades->isEmpty()) {
            return 0;
        }

        $initialBalance = Setting::getValue('initial_balance', 10000);
        $balance = $initialBalance;
        $peak = $balance;
        $maxDrawdown = 0;

        foreach ($trades as $trade) {
            if ($trade->status === 'CLOSED') {
                $balance += $trade->pnl;

                if ($balance > $peak) {
                    $peak = $balance;
                }

                $drawdown = ($peak - $balance) / $peak;
                $maxDrawdown = max($maxDrawdown, $drawdown);
            }
        }

        return $maxDrawdown;
    }

    /**
     * Get current drawdown
     */
    private function getCurrentDrawdown(): float
    {
        $trades = Trade::orderBy('created_at', 'asc')->get();

        if ($trades->isEmpty()) {
            return 0;
        }

        $initialBalance = Setting::getValue('initial_balance', 10000);
        $balance = $initialBalance;
        $peak = $balance;

        foreach ($trades as $trade) {
            if ($trade->status === 'CLOSED') {
                $balance += $trade->pnl;

                if ($balance > $peak) {
                    $peak = $balance;
                }
            }
        }

        // Add unrealized PnL
        $snapshot = $this->getPortfolioSnapshot();
        $currentValue = $balance + $snapshot['unrealized_pnl'];

        if ($currentValue > $peak) {
            return 0;
        }

        return ($peak - $currentValue) / $peak;
    }

    /**
     * Calculate daily returns from trades
     */
    private function calculateDailyReturns(array $trades): array
    {
        $returns = [];
        $accountBalance = $this->getAccountBalance();

        foreach ($trades as $trade) {
            if ($trade->status === 'CLOSED' && $trade->pnl !== null) {
                $return = $trade->pnl / $accountBalance;
                $returns[] = $return;
            }
        }

        return $returns;
    }

    /**
     * Assess overall risk status
     */
    private function assessRiskStatus(float $riskRatio, float $drawdown): string
    {
        if ($drawdown > $this->maxDrawdown || $riskRatio > $this->maxPortfolioRisk) {
            return 'CRITICAL';
        } elseif ($drawdown > $this->maxDrawdown * 0.7 || $riskRatio > $this->maxPortfolioRisk * 0.8) {
            return 'WARNING';
        } elseif ($riskRatio > $this->maxPortfolioRisk * 0.5) {
            return 'ELEVATED';
        } else {
            return 'NORMAL';
        }
    }

    // Helper methods

    private function getOpenPositions()
    {
        return Trade::where('status', 'OPEN')->get();
    }

    private function getClosedTrades(int $days)
    {
        return Trade::where('status', 'CLOSED')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();
    }

    private function getAccountBalance(): float
    {
        return Setting::getValue('account_balance', 10000);
    }

    private function getActivePairs(): array
    {
        $pairs = Setting::getValue('trading_pairs', 'BTCUSDT,ETHUSDT,BNBUSDT');

        return explode(',', $pairs);
    }

    private function calculateUnrealizedPnL($position): float
    {
        // Would fetch current price and calculate
        // Simplified for now
        return 0;
    }

    private function calculatePositionRisk($position): float
    {
        $riskAmount = abs($position->entry_price - ($position->stop_loss ?? $position->entry_price * 0.98)) * $position->quantity;

        return $riskAmount;
    }

    private function calculatePortfolioRisk(): float
    {
        $openPositions = $this->getOpenPositions();
        $totalRisk = 0;

        foreach ($openPositions as $position) {
            $totalRisk += $this->calculatePositionRisk($position);
        }

        return $totalRisk;
    }

    private function estimatePositionRisk(float $size, float $price): float
    {
        // Assume 2% stop loss
        return $size * $price * 0.02;
    }

    private function getPairExposure(string $symbol): float
    {
        $positions = Trade::where('symbol', $symbol)
            ->where('status', 'OPEN')
            ->get();

        $exposure = 0;
        foreach ($positions as $position) {
            $exposure += abs($position->entry_price * $position->quantity);
        }

        return $exposure;
    }

    private function getCorrelatedPairs(string $symbol): array
    {
        $correlationMatrix = $this->calculateCorrelationMatrix();
        $correlated = [];

        if (! isset($correlationMatrix[$symbol])) {
            return [];
        }

        foreach ($correlationMatrix[$symbol] as $pair => $correlation) {
            if ($pair !== $symbol && $correlation > $this->correlationThreshold) {
                $correlated[] = $pair;
            }
        }

        return $correlated;
    }

    private function getCorrelatedExposure(string $symbol, array $correlatedPairs): float
    {
        $exposure = 0;

        foreach ($correlatedPairs as $pair) {
            $exposure += $this->getPairExposure($pair);
        }

        return $exposure;
    }

    private function getDailyPnL(): float
    {
        $today = now()->startOfDay();

        $closedToday = Trade::where('status', 'CLOSED')
            ->where('updated_at', '>=', $today)
            ->sum('pnl');

        return $closedToday ?? 0;
    }
}
