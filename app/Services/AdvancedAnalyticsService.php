<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\AiDecision;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Advanced Analytics Service
 *
 * Comprehensive analytics and metrics system for deep trading performance analysis.
 * Provides statistical insights, performance tracking, and detailed reporting.
 *
 * Key Features:
 * - Performance metrics calculation
 * - Statistical analysis of trades
 * - Time-based performance breakdown
 * - Strategy comparison and analysis
 * - Market condition performance
 * - Trade distribution analysis
 * - Probability and expectancy calculations
 * - Advanced charting data preparation
 * - Machine learning feature extraction
 *
 * Metrics Covered:
 * - Return metrics (Total, Average, Median, Best, Worst)
 * - Risk metrics (Sharpe, Sortino, Calmar, Omega)
 * - Drawdown analysis (Max, Average, Recovery time)
 * - Win rate and profit factor
 * - Expectancy and expected value
 * - Consistency metrics
 * - Trade quality scores
 * - Time analysis (best/worst hours, days, months)
 * - Pair performance comparison
 * - Strategy effectiveness
 */
class AdvancedAnalyticsService
{
    /**
     * Get comprehensive performance overview
     */
    public function getPerformanceOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $trades = $this->getTradesInPeriod($startDate, $endDate);

        if ($trades->isEmpty()) {
            return $this->getEmptyOverview();
        }

        $totalTrades = $trades->count();
        $closedTrades = $trades->where('status', 'CLOSED');
        $winningTrades = $closedTrades->where('pnl', '>', 0);
        $losingTrades = $closedTrades->where('pnl', '<', 0);
        $breakEvenTrades = $closedTrades->where('pnl', '=', 0);

        $totalPnL = $closedTrades->sum('pnl');
        $totalProfit = $winningTrades->sum('pnl');
        $totalLoss = abs($losingTrades->sum('pnl'));

        $winRate = $closedTrades->count() > 0 ? ($winningTrades->count() / $closedTrades->count()) * 100 : 0;
        $avgWin = $winningTrades->count() > 0 ? $winningTrades->avg('pnl') : 0;
        $avgLoss = $losingTrades->count() > 0 ? abs($losingTrades->avg('pnl')) : 0;
        $avgPnL = $closedTrades->count() > 0 ? $closedTrades->avg('pnl') : 0;

        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
        $expectancy = $this->calculateExpectancy($winRate, $avgWin, $avgLoss);

        // Risk metrics
        $sharpeRatio = $this->calculateSharpeRatio($closedTrades);
        $sortinoRatio = $this->calculateSortinoRatio($closedTrades);
        $calmarRatio = $this->calculateCalmarRatio($closedTrades);

        // Drawdown
        $drawdownAnalysis = $this->analyzeDrawdown($closedTrades);

        // Consistency
        $consistencyScore = $this->calculateConsistencyScore($closedTrades);

        // Best and worst trades
        $bestTrade = $closedTrades->sortByDesc('pnl')->first();
        $worstTrade = $closedTrades->sortBy('pnl')->first();

        // Average trade duration
        $avgDuration = $this->calculateAverageTradeDuration($closedTrades);

        return [
            'summary' => [
                'total_trades' => $totalTrades,
                'closed_trades' => $closedTrades->count(),
                'winning_trades' => $winningTrades->count(),
                'losing_trades' => $losingTrades->count(),
                'break_even_trades' => $breakEvenTrades->count(),
                'open_positions' => $trades->where('status', 'OPEN')->count(),
            ],
            'returns' => [
                'total_pnl' => round($totalPnL, 2),
                'total_profit' => round($totalProfit, 2),
                'total_loss' => round($totalLoss, 2),
                'avg_win' => round($avgWin, 2),
                'avg_loss' => round($avgLoss, 2),
                'avg_pnl' => round($avgPnL, 2),
                'best_trade' => $bestTrade ? round($bestTrade->pnl, 2) : 0,
                'worst_trade' => $worstTrade ? round($worstTrade->pnl, 2) : 0,
            ],
            'performance_ratios' => [
                'win_rate' => round($winRate, 2),
                'loss_rate' => round(100 - $winRate, 2),
                'profit_factor' => round($profitFactor, 2),
                'expectancy' => round($expectancy, 2),
                'avg_risk_reward' => $avgLoss > 0 ? round($avgWin / $avgLoss, 2) : 0,
            ],
            'risk_metrics' => [
                'sharpe_ratio' => round($sharpeRatio, 3),
                'sortino_ratio' => round($sortinoRatio, 3),
                'calmar_ratio' => round($calmarRatio, 3),
                'max_drawdown' => round($drawdownAnalysis['max_drawdown'] * 100, 2),
                'avg_drawdown' => round($drawdownAnalysis['avg_drawdown'] * 100, 2),
                'recovery_factor' => round($drawdownAnalysis['recovery_factor'], 2),
            ],
            'quality_metrics' => [
                'consistency_score' => round($consistencyScore, 2),
                'avg_trade_duration' => $avgDuration,
                'trades_per_day' => $this->calculateTradesPerDay($closedTrades, $startDate, $endDate),
            ],
        ];
    }

    /**
     * Get performance breakdown by time periods
     */
    public function getTimeBasedPerformance(): array
    {
        $trades = Trade::where('status', 'CLOSED')->get();

        return [
            'by_hour' => $this->groupByHour($trades),
            'by_day_of_week' => $this->groupByDayOfWeek($trades),
            'by_month' => $this->groupByMonth($trades),
            'by_year' => $this->groupByYear($trades),
        ];
    }

    /**
     * Get performance by trading pair
     */
    public function getPerformanceByPair(): array
    {
        $trades = Trade::where('status', 'CLOSED')->get();
        $groupedByPair = $trades->groupBy('symbol');

        $performance = [];

        foreach ($groupedByPair as $symbol => $pairTrades) {
            $winningTrades = $pairTrades->where('pnl', '>', 0);
            $losingTrades = $pairTrades->where('pnl', '<', 0);

            $totalPnL = $pairTrades->sum('pnl');
            $winRate = $pairTrades->count() > 0 ? ($winningTrades->count() / $pairTrades->count()) * 100 : 0;
            $avgPnL = $pairTrades->avg('pnl');

            $performance[$symbol] = [
                'symbol' => $symbol,
                'total_trades' => $pairTrades->count(),
                'winning_trades' => $winningTrades->count(),
                'losing_trades' => $losingTrades->count(),
                'total_pnl' => round($totalPnL, 2),
                'avg_pnl' => round($avgPnL, 2),
                'win_rate' => round($winRate, 2),
                'best_trade' => round($pairTrades->max('pnl'), 2),
                'worst_trade' => round($pairTrades->min('pnl'), 2),
            ];
        }

        // Sort by total PnL descending
        uasort($performance, function($a, $b) {
            return $b['total_pnl'] <=> $a['total_pnl'];
        });

        return $performance;
    }

    /**
     * Get performance by strategy
     */
    public function getPerformanceByStrategy(): array
    {
        $decisions = AiDecision::with('trade')->get();
        $groupedByStrategy = $decisions->groupBy('strategy_used');

        $performance = [];

        foreach ($groupedByStrategy as $strategy => $strategyDecisions) {
            $trades = $strategyDecisions->pluck('trade')->filter()->where('status', 'CLOSED');

            if ($trades->isEmpty()) {
                continue;
            }

            $winningTrades = $trades->where('pnl', '>', 0);
            $totalPnL = $trades->sum('pnl');
            $winRate = $trades->count() > 0 ? ($winningTrades->count() / $trades->count()) * 100 : 0;

            $performance[$strategy] = [
                'strategy' => $strategy,
                'total_trades' => $trades->count(),
                'winning_trades' => $winningTrades->count(),
                'total_pnl' => round($totalPnL, 2),
                'avg_pnl' => round($trades->avg('pnl'), 2),
                'win_rate' => round($winRate, 2),
                'avg_confidence' => round($strategyDecisions->avg('confidence'), 2),
            ];
        }

        uasort($performance, function($a, $b) {
            return $b['total_pnl'] <=> $a['total_pnl'];
        });

        return $performance;
    }

    /**
     * Get trade distribution analysis
     */
    public function getTradeDistribution(): array
    {
        $trades = Trade::where('status', 'CLOSED')->get();
        $pnls = $trades->pluck('pnl')->toArray();

        if (empty($pnls)) {
            return [];
        }

        // Calculate distribution metrics
        $mean = array_sum($pnls) / count($pnls);
        $median = $this->calculateMedian($pnls);
        $mode = $this->calculateMode($pnls);
        $stdDev = $this->calculateStdDev($pnls, $mean);
        $skewness = $this->calculateSkewness($pnls, $mean, $stdDev);
        $kurtosis = $this->calculateKurtosis($pnls, $mean, $stdDev);

        // Percentiles
        $percentiles = $this->calculatePercentiles($pnls);

        // Create histogram buckets
        $histogram = $this->createHistogram($pnls);

        return [
            'central_tendency' => [
                'mean' => round($mean, 2),
                'median' => round($median, 2),
                'mode' => round($mode, 2),
            ],
            'dispersion' => [
                'std_dev' => round($stdDev, 2),
                'variance' => round($stdDev ** 2, 2),
                'range' => round(max($pnls) - min($pnls), 2),
                'iqr' => round($percentiles[75] - $percentiles[25], 2),
            ],
            'shape' => [
                'skewness' => round($skewness, 3),
                'kurtosis' => round($kurtosis, 3),
                'interpretation' => $this->interpretDistributionShape($skewness, $kurtosis),
            ],
            'percentiles' => $percentiles,
            'histogram' => $histogram,
        ];
    }

    /**
     * Get win/loss streak analysis
     */
    public function getStreakAnalysis(): array
    {
        $trades = Trade::where('status', 'CLOSED')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($trades->isEmpty()) {
            return [];
        }

        $currentStreak = 0;
        $currentType = null;
        $maxWinStreak = 0;
        $maxLossStreak = 0;
        $winStreaks = [];
        $lossStreaks = [];

        foreach ($trades as $trade) {
            $isWin = $trade->pnl > 0;

            if ($currentType === null) {
                $currentType = $isWin ? 'WIN' : 'LOSS';
                $currentStreak = 1;
            } elseif (($isWin && $currentType === 'WIN') || (!$isWin && $currentType === 'LOSS')) {
                $currentStreak++;
            } else {
                // Streak ended, record it
                if ($currentType === 'WIN') {
                    $winStreaks[] = $currentStreak;
                    $maxWinStreak = max($maxWinStreak, $currentStreak);
                } else {
                    $lossStreaks[] = $currentStreak;
                    $maxLossStreak = max($maxLossStreak, $currentStreak);
                }

                // Start new streak
                $currentType = $isWin ? 'WIN' : 'LOSS';
                $currentStreak = 1;
            }
        }

        // Record final streak
        if ($currentType === 'WIN') {
            $winStreaks[] = $currentStreak;
            $maxWinStreak = max($maxWinStreak, $currentStreak);
        } else {
            $lossStreaks[] = $currentStreak;
            $maxLossStreak = max($maxLossStreak, $currentStreak);
        }

        return [
            'max_win_streak' => $maxWinStreak,
            'max_loss_streak' => $maxLossStreak,
            'avg_win_streak' => !empty($winStreaks) ? round(array_sum($winStreaks) / count($winStreaks), 2) : 0,
            'avg_loss_streak' => !empty($lossStreaks) ? round(array_sum($lossStreaks) / count($lossStreaks), 2) : 0,
            'current_streak' => $currentStreak,
            'current_type' => $currentType,
        ];
    }

    /**
     * Get equity curve data for charting
     */
    public function getEquityCurve(): array
    {
        $initialBalance = Setting::getValue('initial_balance', 10000);
        $trades = Trade::where('status', 'CLOSED')
            ->orderBy('created_at', 'asc')
            ->get();

        $equityCurve = [];
        $balance = $initialBalance;
        $peak = $initialBalance;
        $drawdown = 0;

        $equityCurve[] = [
            'date' => null,
            'balance' => $balance,
            'peak' => $peak,
            'drawdown' => 0,
        ];

        foreach ($trades as $trade) {
            $balance += $trade->pnl;

            if ($balance > $peak) {
                $peak = $balance;
                $drawdown = 0;
            } else {
                $drawdown = (($peak - $balance) / $peak) * 100;
            }

            $equityCurve[] = [
                'date' => $trade->updated_at->format('Y-m-d H:i:s'),
                'balance' => round($balance, 2),
                'peak' => round($peak, 2),
                'drawdown' => round($drawdown, 2),
                'pnl' => round($trade->pnl, 2),
                'symbol' => $trade->symbol,
            ];
        }

        return $equityCurve;
    }

    /**
     * Get Monte Carlo simulation results
     */
    public function runMonteCarloSimulation(int $simulations = 1000, int $trades = 100): array
    {
        $historicalTrades = Trade::where('status', 'CLOSED')->get();

        if ($historicalTrades->isEmpty()) {
            return [];
        }

        $historicalReturns = $historicalTrades->pluck('pnl')->toArray();
        $initialBalance = Setting::getValue('initial_balance', 10000);

        $simulationResults = [];
        $finalBalances = [];

        for ($sim = 0; $sim < $simulations; $sim++) {
            $balance = $initialBalance;

            for ($t = 0; $t < $trades; $t++) {
                // Randomly sample from historical returns
                $randomReturn = $historicalReturns[array_rand($historicalReturns)];
                $balance += $randomReturn;

                if ($balance <= 0) {
                    break; // Bust
                }
            }

            $finalBalances[] = $balance;
            $simulationResults[] = [
                'simulation' => $sim + 1,
                'final_balance' => round($balance, 2),
                'return' => round((($balance - $initialBalance) / $initialBalance) * 100, 2),
            ];
        }

        // Calculate statistics
        sort($finalBalances);

        return [
            'simulations' => $simulations,
            'trades_per_sim' => $trades,
            'statistics' => [
                'mean_final_balance' => round(array_sum($finalBalances) / count($finalBalances), 2),
                'median_final_balance' => round($this->calculateMedian($finalBalances), 2),
                'min_final_balance' => round(min($finalBalances), 2),
                'max_final_balance' => round(max($finalBalances), 2),
                'percentile_5' => round($finalBalances[(int)(count($finalBalances) * 0.05)], 2),
                'percentile_95' => round($finalBalances[(int)(count($finalBalances) * 0.95)], 2),
            ],
            'risk_metrics' => [
                'probability_of_profit' => round((count(array_filter($finalBalances, fn($b) => $b > $initialBalance)) / count($finalBalances)) * 100, 2),
                'probability_of_ruin' => round((count(array_filter($finalBalances, fn($b) => $b <= $initialBalance * 0.5)) / count($finalBalances)) * 100, 2),
            ],
        ];
    }

    /**
     * Get trade quality scores
     */
    public function getTradeQualityScores(): array
    {
        $trades = Trade::where('status', 'CLOSED')->with('aiDecision')->get();

        $qualityScores = [];

        foreach ($trades as $trade) {
            $score = $this->calculateTradeQualityScore($trade);
            $qualityScores[] = array_merge($score, [
                'trade_id' => $trade->id,
                'symbol' => $trade->symbol,
                'pnl' => $trade->pnl,
            ]);
        }

        // Sort by quality score
        usort($qualityScores, function($a, $b) {
            return $b['total_score'] <=> $a['total_score'];
        });

        return [
            'scores' => $qualityScores,
            'avg_quality' => round(array_sum(array_column($qualityScores, 'total_score')) / count($qualityScores), 2),
            'best_trades' => array_slice($qualityScores, 0, 10),
            'worst_trades' => array_slice(array_reverse($qualityScores), 0, 10),
        ];
    }

    // Helper Methods

    private function getTradesInPeriod(?Carbon $start, ?Carbon $end)
    {
        $query = Trade::query();

        if ($start) {
            $query->where('created_at', '>=', $start);
        }

        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        return $query->get();
    }

    private function getEmptyOverview(): array
    {
        return [
            'summary' => ['total_trades' => 0],
            'returns' => [],
            'performance_ratios' => [],
            'risk_metrics' => [],
            'quality_metrics' => [],
        ];
    }

    private function calculateExpectancy(float $winRate, float $avgWin, float $avgLoss): float
    {
        $winRate = $winRate / 100;
        $lossRate = 1 - $winRate;

        return ($winRate * $avgWin) - ($lossRate * $avgLoss);
    }

    private function calculateSharpeRatio($trades): float
    {
        if ($trades->isEmpty()) {
            return 0;
        }

        $returns = $trades->pluck('pnl')->toArray();
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns, $avgReturn);

        return $stdDev > 0 ? $avgReturn / $stdDev : 0;
    }

    private function calculateSortinoRatio($trades): float
    {
        if ($trades->isEmpty()) {
            return 0;
        }

        $returns = $trades->pluck('pnl')->toArray();
        $avgReturn = array_sum($returns) / count($returns);

        // Downside deviation
        $negativeReturns = array_filter($returns, fn($r) => $r < 0);
        if (empty($negativeReturns)) {
            return $avgReturn > 0 ? 999 : 0;
        }

        $downsideVariance = array_sum(array_map(fn($r) => $r ** 2, $negativeReturns)) / count($returns);
        $downsideStdDev = sqrt($downsideVariance);

        return $downsideStdDev > 0 ? $avgReturn / $downsideStdDev : 0;
    }

    private function calculateCalmarRatio($trades): float
    {
        if ($trades->isEmpty()) {
            return 0;
        }

        $totalReturn = $trades->sum('pnl');
        $drawdown = $this->analyzeDrawdown($trades);
        $maxDrawdown = $drawdown['max_drawdown'];

        return $maxDrawdown > 0 ? $totalReturn / $maxDrawdown : 0;
    }

    private function analyzeDrawdown($trades): array
    {
        $balance = Setting::getValue('initial_balance', 10000);
        $peak = $balance;
        $maxDrawdown = 0;
        $drawdowns = [];
        $inDrawdown = false;
        $drawdownStart = null;

        foreach ($trades as $trade) {
            $balance += $trade->pnl;

            if ($balance > $peak) {
                if ($inDrawdown) {
                    // Drawdown ended
                    $inDrawdown = false;
                }
                $peak = $balance;
            } else {
                $currentDrawdown = ($peak - $balance) / $peak;
                $maxDrawdown = max($maxDrawdown, $currentDrawdown);

                if (!$inDrawdown) {
                    $inDrawdown = true;
                    $drawdownStart = $trade->created_at;
                }

                if ($currentDrawdown > 0) {
                    $drawdowns[] = $currentDrawdown;
                }
            }
        }

        $avgDrawdown = !empty($drawdowns) ? array_sum($drawdowns) / count($drawdowns) : 0;
        $recoveryFactor = $maxDrawdown > 0 ? $trades->sum('pnl') / ($maxDrawdown * $peak) : 0;

        return [
            'max_drawdown' => $maxDrawdown,
            'avg_drawdown' => $avgDrawdown,
            'recovery_factor' => $recoveryFactor,
        ];
    }

    private function calculateConsistencyScore($trades): float
    {
        if ($trades->count() < 10) {
            return 0;
        }

        // Break into chunks of 10 trades
        $chunks = $trades->chunk(10);
        $chunkReturns = [];

        foreach ($chunks as $chunk) {
            $chunkReturns[] = $chunk->sum('pnl');
        }

        // Calculate coefficient of variation (lower is more consistent)
        $mean = array_sum($chunkReturns) / count($chunkReturns);
        $stdDev = $this->calculateStdDev($chunkReturns, $mean);

        if ($mean == 0) {
            return 0;
        }

        $cv = $stdDev / abs($mean);

        // Convert to 0-100 score (lower CV = higher score)
        $score = max(0, 100 - ($cv * 50));

        return $score;
    }

    private function calculateAverageTradeDuration($trades): string
    {
        $durations = [];

        foreach ($trades as $trade) {
            if ($trade->created_at && $trade->updated_at) {
                $durations[] = $trade->created_at->diffInMinutes($trade->updated_at);
            }
        }

        if (empty($durations)) {
            return '0 minutes';
        }

        $avgMinutes = array_sum($durations) / count($durations);

        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' minutes';
        } elseif ($avgMinutes < 1440) {
            return round($avgMinutes / 60, 1) . ' hours';
        } else {
            return round($avgMinutes / 1440, 1) . ' days';
        }
    }

    private function calculateTradesPerDay($trades, ?Carbon $start, ?Carbon $end): float
    {
        if ($trades->isEmpty()) {
            return 0;
        }

        $start = $start ?? $trades->min('created_at');
        $end = $end ?? $trades->max('created_at');

        $days = $start->diffInDays($end);
        if ($days == 0) {
            $days = 1;
        }

        return round($trades->count() / $days, 2);
    }

    private function groupByHour($trades): array
    {
        $hourlyStats = array_fill(0, 24, ['count' => 0, 'pnl' => 0]);

        foreach ($trades as $trade) {
            $hour = (int) $trade->created_at->format('H');
            $hourlyStats[$hour]['count']++;
            $hourlyStats[$hour]['pnl'] += $trade->pnl;
        }

        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[] = [
                'hour' => $h,
                'count' => $hourlyStats[$h]['count'],
                'total_pnl' => round($hourlyStats[$h]['pnl'], 2),
                'avg_pnl' => $hourlyStats[$h]['count'] > 0 ? round($hourlyStats[$h]['pnl'] / $hourlyStats[$h]['count'], 2) : 0,
            ];
        }

        return $result;
    }

    private function groupByDayOfWeek($trades): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayStats = array_fill(0, 7, ['count' => 0, 'pnl' => 0]);

        foreach ($trades as $trade) {
            $dayIndex = (int) $trade->created_at->format('N') - 1; // 1-7 to 0-6
            $dayStats[$dayIndex]['count']++;
            $dayStats[$dayIndex]['pnl'] += $trade->pnl;
        }

        $result = [];
        for ($d = 0; $d < 7; $d++) {
            $result[] = [
                'day' => $days[$d],
                'count' => $dayStats[$d]['count'],
                'total_pnl' => round($dayStats[$d]['pnl'], 2),
                'avg_pnl' => $dayStats[$d]['count'] > 0 ? round($dayStats[$d]['pnl'] / $dayStats[$d]['count'], 2) : 0,
            ];
        }

        return $result;
    }

    private function groupByMonth($trades): array
    {
        return $trades->groupBy(function($trade) {
            return $trade->created_at->format('Y-m');
        })->map(function($monthTrades, $month) {
            return [
                'month' => $month,
                'count' => $monthTrades->count(),
                'total_pnl' => round($monthTrades->sum('pnl'), 2),
                'avg_pnl' => round($monthTrades->avg('pnl'), 2),
            ];
        })->values()->toArray();
    }

    private function groupByYear($trades): array
    {
        return $trades->groupBy(function($trade) {
            return $trade->created_at->format('Y');
        })->map(function($yearTrades, $year) {
            return [
                'year' => $year,
                'count' => $yearTrades->count(),
                'total_pnl' => round($yearTrades->sum('pnl'), 2),
                'avg_pnl' => round($yearTrades->avg('pnl'), 2),
            ];
        })->values()->toArray();
    }

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count == 0) {
            return 0;
        }

        $mid = floor($count / 2);

        if ($count % 2 == 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2;
        } else {
            return $values[$mid];
        }
    }

    private function calculateMode(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        $frequencies = array_count_values(array_map('round', $values));
        arsort($frequencies);

        return (float) array_key_first($frequencies);
    }

    private function calculateStdDev(array $values, float $mean): float
    {
        if (empty($values)) {
            return 0;
        }

        $squaredDiffs = array_map(fn($v) => ($v - $mean) ** 2, $values);
        $variance = array_sum($squaredDiffs) / count($values);

        return sqrt($variance);
    }

    private function calculateSkewness(array $values, float $mean, float $stdDev): float
    {
        if ($stdDev == 0 || empty($values)) {
            return 0;
        }

        $cubedDiffs = array_map(fn($v) => (($v - $mean) / $stdDev) ** 3, $values);

        return array_sum($cubedDiffs) / count($values);
    }

    private function calculateKurtosis(array $values, float $mean, float $stdDev): float
    {
        if ($stdDev == 0 || empty($values)) {
            return 0;
        }

        $fourthPowers = array_map(fn($v) => (($v - $mean) / $stdDev) ** 4, $values);

        return (array_sum($fourthPowers) / count($values)) - 3; // Excess kurtosis
    }

    private function calculatePercentiles(array $values): array
    {
        sort($values);
        $count = count($values);

        $percentiles = [];
        foreach ([5, 10, 25, 50, 75, 90, 95] as $p) {
            $index = (int) floor(($p / 100) * $count);
            $index = max(0, min($index, $count - 1));
            $percentiles[$p] = round($values[$index], 2);
        }

        return $percentiles;
    }

    private function createHistogram(array $values, int $buckets = 20): array
    {
        if (empty($values)) {
            return [];
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;

        if ($range == 0) {
            return [['bucket' => $min, 'count' => count($values)]];
        }

        $bucketSize = $range / $buckets;
        $histogram = array_fill(0, $buckets, 0);

        foreach ($values as $value) {
            $bucketIndex = min((int) floor(($value - $min) / $bucketSize), $buckets - 1);
            $histogram[$bucketIndex]++;
        }

        $result = [];
        for ($i = 0; $i < $buckets; $i++) {
            $bucketStart = $min + ($i * $bucketSize);
            $result[] = [
                'range' => round($bucketStart, 2) . ' to ' . round($bucketStart + $bucketSize, 2),
                'count' => $histogram[$i],
            ];
        }

        return $result;
    }

    private function interpretDistributionShape(float $skewness, float $kurtosis): string
    {
        $interpretation = [];

        if (abs($skewness) < 0.5) {
            $interpretation[] = 'Symmetric distribution';
        } elseif ($skewness > 0.5) {
            $interpretation[] = 'Right-skewed (more large wins)';
        } else {
            $interpretation[] = 'Left-skewed (more large losses)';
        }

        if ($kurtosis > 1) {
            $interpretation[] = 'Heavy tails (more extreme outcomes)';
        } elseif ($kurtosis < -1) {
            $interpretation[] = 'Light tails (fewer extreme outcomes)';
        } else {
            $interpretation[] = 'Normal distribution';
        }

        return implode(', ', $interpretation);
    }

    private function calculateTradeQualityScore($trade): array
    {
        $score = 0;
        $breakdown = [];

        // 1. Risk/Reward ratio (20 points)
        if ($trade->stop_loss && $trade->take_profit) {
            $risk = abs($trade->entry_price - $trade->stop_loss);
            $reward = abs($trade->take_profit - $trade->entry_price);
            $rr = $risk > 0 ? $reward / $risk : 0;

            if ($rr >= 3) {
                $score += 20;
                $breakdown['risk_reward'] = 20;
            } elseif ($rr >= 2) {
                $score += 15;
                $breakdown['risk_reward'] = 15;
            } elseif ($rr >= 1.5) {
                $score += 10;
                $breakdown['risk_reward'] = 10;
            }
        }

        // 2. Profitability (30 points)
        if ($trade->pnl > 0) {
            $score += 30;
            $breakdown['profitability'] = 30;
        }

        // 3. AI Confidence (25 points)
        if ($trade->aiDecision) {
            $confidence = $trade->aiDecision->confidence;
            $score += ($confidence / 100) * 25;
            $breakdown['ai_confidence'] = round(($confidence / 100) * 25, 2);
        }

        // 4. Trade execution (15 points)
        // Check if trade hit target or stop
        if ($trade->pnl > 0 && $trade->exit_price >= $trade->take_profit * 0.95) {
            $score += 15; // Hit target
            $breakdown['execution'] = 15;
        } elseif ($trade->pnl < 0 && abs($trade->exit_price - $trade->stop_loss) / $trade->stop_loss < 0.05) {
            $score += 10; // Proper stop loss
            $breakdown['execution'] = 10;
        }

        // 5. Trade duration (10 points)
        if ($trade->created_at && $trade->updated_at) {
            $duration = $trade->created_at->diffInHours($trade->updated_at);

            if ($duration < 24) {
                $score += 10; // Quick trades
                $breakdown['duration'] = 10;
            } elseif ($duration < 72) {
                $score += 5;
                $breakdown['duration'] = 5;
            }
        }

        return [
            'total_score' => round($score, 2),
            'breakdown' => $breakdown,
            'grade' => $this->scoreToGrade($score),
        ];
    }

    private function scoreToGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
}
