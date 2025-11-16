<?php

namespace App\Livewire\Dashboard;

use App\Models\Trade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class AdvancedMetrics extends Component
{
    public array $metrics = [];

    public array $equityCurve = [];

    public array $monthlyPerformance = [];

    public array $hourlyPerformance = [];

    public array $pairPerformance = [];

    public array $streakAnalysis = [];

    public string $selectedPeriod = '30d';

    public bool $isLoading = false;

    public function mount(): void
    {
        $this->loadMetrics();
    }

    #[On('refresh-metrics')]
    public function loadMetrics(): void
    {
        $this->isLoading = true;

        $cacheKey = "advanced_metrics:{$this->selectedPeriod}";

        $metricsData = Cache::remember($cacheKey, 300, function () {
            // Get date range based on selected period
            $dateRange = $this->getDateRange();

            // Get all closed trades in the period
            $trades = Trade::where('status', 'CLOSED')
                ->where('closed_at', '>=', $dateRange['start'])
                ->where('closed_at', '<=', $dateRange['end'])
                ->orderBy('closed_at')
                ->get();

            if ($trades->isEmpty()) {
                return $this->getEmptyMetrics();
            }

            // Calculate all metrics
            return [
                'basic' => $this->calculateBasicMetrics($trades),
                'risk' => $this->calculateRiskMetrics($trades),
                'distribution' => $this->calculateDistributionMetrics($trades),
                'quality' => $this->calculateQualityMetrics($trades),
            ];
        });

        $this->metrics = $metricsData;

        // Generate additional analysis
        $this->generateEquityCurve();
        $this->generateMonthlyPerformance();
        $this->generateHourlyPerformance();
        $this->generatePairPerformance();
        $this->generateStreakAnalysis();

        $this->isLoading = false;
    }

    private function getDateRange(): array
    {
        $end = now();

        $start = match ($this->selectedPeriod) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            'all' => Trade::min('created_at') ?? now()->subYear(),
            default => now()->subDays(30),
        };

        return ['start' => $start, 'end' => $end];
    }

    private function getEmptyMetrics(): array
    {
        return [
            'basic' => [
                'total_trades' => 0,
                'winning_trades' => 0,
                'losing_trades' => 0,
                'win_rate' => 0,
                'total_pnl' => 0,
                'avg_win' => 0,
                'avg_loss' => 0,
                'largest_win' => 0,
                'largest_loss' => 0,
                'profit_factor' => 0,
                'expectancy' => 0,
            ],
            'risk' => [
                'sharpe_ratio' => 0,
                'sortino_ratio' => 0,
                'calmar_ratio' => 0,
                'max_drawdown' => 0,
                'max_drawdown_duration' => 0,
                'recovery_factor' => 0,
                'var_95' => 0,
                'cvar_95' => 0,
            ],
            'distribution' => [
                'skewness' => 0,
                'kurtosis' => 0,
                'std_dev' => 0,
                'variance' => 0,
            ],
            'quality' => [
                'avg_duration' => 0,
                'avg_rrr' => 0,
                'win_streak' => 0,
                'loss_streak' => 0,
                'avg_mae' => 0,
                'avg_mfe' => 0,
            ],
        ];
    }

    private function calculateBasicMetrics($trades): array
    {
        $totalTrades = $trades->count();
        $winningTrades = $trades->where('pnl', '>', 0);
        $losingTrades = $trades->where('pnl', '<', 0);

        $winCount = $winningTrades->count();
        $lossCount = $losingTrades->count();

        $totalPnl = $trades->sum('pnl');
        $grossProfit = $winningTrades->sum('pnl');
        $grossLoss = abs($losingTrades->sum('pnl'));

        $avgWin = $winCount > 0 ? $grossProfit / $winCount : 0;
        $avgLoss = $lossCount > 0 ? $grossLoss / $lossCount : 0;

        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : 0;
        $expectancy = $totalTrades > 0 ? $totalPnl / $totalTrades : 0;

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winCount,
            'losing_trades' => $lossCount,
            'win_rate' => $totalTrades > 0 ? ($winCount / $totalTrades) * 100 : 0,
            'total_pnl' => $totalPnl,
            'gross_profit' => $grossProfit,
            'gross_loss' => $grossLoss,
            'avg_win' => $avgWin,
            'avg_loss' => $avgLoss,
            'largest_win' => $winningTrades->max('pnl') ?? 0,
            'largest_loss' => $losingTrades->min('pnl') ?? 0,
            'profit_factor' => $profitFactor,
            'expectancy' => $expectancy,
        ];
    }

    private function calculateRiskMetrics($trades): array
    {
        $returns = $trades->pluck('pnl')->toArray();

        if (empty($returns)) {
            return array_fill_keys(['sharpe_ratio', 'sortino_ratio', 'calmar_ratio', 'max_drawdown', 'max_drawdown_duration', 'recovery_factor', 'var_95', 'cvar_95'], 0);
        }

        // Calculate returns statistics
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);

        // Sharpe Ratio (assuming risk-free rate = 0)
        $sharpeRatio = $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0;

        // Sortino Ratio (only downside deviation)
        $downsideReturns = array_filter($returns, fn ($r) => $r < 0);
        $downsideStdDev = ! empty($downsideReturns) ? $this->calculateStdDev($downsideReturns) : $stdDev;
        $sortinoRatio = $downsideStdDev > 0 ? ($avgReturn / $downsideStdDev) * sqrt(252) : 0;

        // Maximum Drawdown
        $drawdownData = $this->calculateMaxDrawdown($trades);

        // Calmar Ratio
        $annualReturn = $avgReturn * 252;
        $calmarRatio = $drawdownData['max_drawdown'] > 0 ? abs($annualReturn / $drawdownData['max_drawdown']) : 0;

        // Recovery Factor
        $totalReturn = array_sum($returns);
        $recoveryFactor = $drawdownData['max_drawdown'] > 0 ? abs($totalReturn / $drawdownData['max_drawdown']) : 0;

        // Value at Risk (95% confidence)
        $sortedReturns = $returns;
        sort($sortedReturns);
        $var95Index = (int) floor(count($sortedReturns) * 0.05);
        $var95 = $sortedReturns[$var95Index] ?? 0;

        // Conditional Value at Risk (CVaR)
        $cvar95 = count($sortedReturns) > 0 ? array_sum(array_slice($sortedReturns, 0, $var95Index + 1)) / max(1, $var95Index + 1) : 0;

        return [
            'sharpe_ratio' => $sharpeRatio,
            'sortino_ratio' => $sortinoRatio,
            'calmar_ratio' => $calmarRatio,
            'max_drawdown' => $drawdownData['max_drawdown'],
            'max_drawdown_duration' => $drawdownData['duration_days'],
            'recovery_factor' => $recoveryFactor,
            'var_95' => $var95,
            'cvar_95' => $cvar95,
        ];
    }

    private function calculateMaxDrawdown($trades): array
    {
        // GET REAL STARTING EQUITY - NO HARDCODED VALUES!
        $equity = $this->getStartingEquity($trades);
        $peak = $equity;
        $maxDrawdown = 0;
        $maxDrawdownDuration = 0;
        $currentDrawdownStart = null;

        foreach ($trades as $trade) {
            $equity += $trade->pnl;

            if ($equity > $peak) {
                $peak = $equity;
                $currentDrawdownStart = null;
            } else {
                $drawdown = (($peak - $equity) / $peak) * 100;

                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }

                if ($currentDrawdownStart === null) {
                    $currentDrawdownStart = $trade->closed_at;
                } else {
                    $duration = $currentDrawdownStart->diffInDays($trade->closed_at);
                    $maxDrawdownDuration = max($maxDrawdownDuration, $duration);
                }
            }
        }

        return [
            'max_drawdown' => $maxDrawdown,
            'duration_days' => $maxDrawdownDuration,
        ];
    }

    private function calculateDistributionMetrics($trades): array
    {
        $returns = $trades->pluck('pnl')->toArray();

        if (count($returns) < 3) {
            return array_fill_keys(['skewness', 'kurtosis', 'std_dev', 'variance'], 0);
        }

        $mean = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);
        $variance = pow($stdDev, 2);

        // Skewness
        $skewness = 0;
        foreach ($returns as $return) {
            $skewness += pow(($return - $mean) / $stdDev, 3);
        }
        $skewness = $skewness / count($returns);

        // Kurtosis
        $kurtosis = 0;
        foreach ($returns as $return) {
            $kurtosis += pow(($return - $mean) / $stdDev, 4);
        }
        $kurtosis = ($kurtosis / count($returns)) - 3; // Excess kurtosis

        return [
            'skewness' => $skewness,
            'kurtosis' => $kurtosis,
            'std_dev' => $stdDev,
            'variance' => $variance,
        ];
    }

    private function calculateQualityMetrics($trades): array
    {
        $durations = [];
        $rrrs = [];
        $streaks = ['win' => 0, 'loss' => 0, 'current' => 0, 'type' => null];
        $maxWinStreak = 0;
        $maxLossStreak = 0;

        foreach ($trades as $trade) {
            // Duration
            if ($trade->created_at && $trade->closed_at) {
                $durations[] = $trade->created_at->diffInMinutes($trade->closed_at);
            }

            // Risk/Reward Ratio
            if ($trade->stop_loss && $trade->take_profit) {
                $risk = abs($trade->entry_price - $trade->stop_loss) * $trade->quantity;
                $reward = abs($trade->take_profit - $trade->entry_price) * $trade->quantity;
                if ($risk > 0) {
                    $rrrs[] = $reward / $risk;
                }
            }

            // Streak analysis
            if ($trade->pnl > 0) {
                if ($streaks['type'] === 'win') {
                    $streaks['current']++;
                } else {
                    $maxLossStreak = max($maxLossStreak, $streaks['current']);
                    $streaks['current'] = 1;
                    $streaks['type'] = 'win';
                }
            } else {
                if ($streaks['type'] === 'loss') {
                    $streaks['current']++;
                } else {
                    $maxWinStreak = max($maxWinStreak, $streaks['current']);
                    $streaks['current'] = 1;
                    $streaks['type'] = 'loss';
                }
            }
        }

        // Finalize streak analysis
        if ($streaks['type'] === 'win') {
            $maxWinStreak = max($maxWinStreak, $streaks['current']);
        } else {
            $maxLossStreak = max($maxLossStreak, $streaks['current']);
        }

        return [
            'avg_duration' => ! empty($durations) ? array_sum($durations) / count($durations) : 0,
            'avg_rrr' => ! empty($rrrs) ? array_sum($rrrs) / count($rrrs) : 0,
            'win_streak' => $maxWinStreak,
            'loss_streak' => $maxLossStreak,
            'avg_mae' => 0, // Maximum Adverse Excursion - would need tick data
            'avg_mfe' => 0, // Maximum Favorable Excursion - would need tick data
        ];
    }

    private function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        $variance = $variance / (count($values) - 1);

        return sqrt($variance);
    }

    private function generateEquityCurve(): void
    {
        $dateRange = $this->getDateRange();
        $trades = Trade::where('status', 'CLOSED')
            ->where('closed_at', '>=', $dateRange['start'])
            ->where('closed_at', '<=', $dateRange['end'])
            ->orderBy('closed_at')
            ->get();

        // GET REAL STARTING BALANCE - NO HARDCODED VALUES!
        $equity = $this->getStartingEquity($trades);
        $this->equityCurve = [];

        foreach ($trades as $trade) {
            $equity += $trade->pnl;
            $this->equityCurve[] = [
                'date' => $trade->closed_at->format('Y-m-d H:i'),
                'equity' => $equity,
                'pnl' => $trade->pnl,
            ];
        }
    }

    private function generateMonthlyPerformance(): void
    {
        $this->monthlyPerformance = Trade::where('status', 'CLOSED')
            ->where('closed_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(closed_at, "%Y-%m") as month'),
                DB::raw('SUM(pnl) as total_pnl'),
                DB::raw('COUNT(*) as trade_count'),
                DB::raw('SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function generateHourlyPerformance(): void
    {
        $this->hourlyPerformance = Trade::where('status', 'CLOSED')
            ->where('closed_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('HOUR(closed_at) as hour'),
                DB::raw('SUM(pnl) as total_pnl'),
                DB::raw('COUNT(*) as trade_count'),
                DB::raw('AVG(pnl) as avg_pnl')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    private function generatePairPerformance(): void
    {
        $this->pairPerformance = Trade::where('status', 'CLOSED')
            ->where('closed_at', '>=', now()->subDays(30))
            ->select(
                'symbol',
                DB::raw('SUM(pnl) as total_pnl'),
                DB::raw('COUNT(*) as trade_count'),
                DB::raw('SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades'),
                DB::raw('AVG(pnl) as avg_pnl')
            )
            ->groupBy('symbol')
            ->orderByDesc('total_pnl')
            ->get()
            ->toArray();
    }

    private function generateStreakAnalysis(): void
    {
        $trades = Trade::where('status', 'CLOSED')
            ->orderBy('closed_at')
            ->get();

        $currentStreak = 0;
        $streakType = null;
        $streaks = [];

        foreach ($trades as $trade) {
            if ($trade->pnl > 0) {
                if ($streakType === 'win') {
                    $currentStreak++;
                } else {
                    if ($streakType !== null) {
                        $streaks[] = ['type' => $streakType, 'length' => $currentStreak];
                    }
                    $currentStreak = 1;
                    $streakType = 'win';
                }
            } else {
                if ($streakType === 'loss') {
                    $currentStreak++;
                } else {
                    if ($streakType !== null) {
                        $streaks[] = ['type' => $streakType, 'length' => $currentStreak];
                    }
                    $currentStreak = 1;
                    $streakType = 'loss';
                }
            }
        }

        if ($streakType !== null) {
            $streaks[] = ['type' => $streakType, 'length' => $currentStreak];
        }

        $this->streakAnalysis = $streaks;
    }

    /**
     * Calculate REAL starting equity based on current Binance balance and historical PnL
     * NO HARDCODED VALUES!
     */
    private function getStartingEquity($trades): float
    {
        try {
            $binance = app(\App\Services\BinanceService::class);
            $currentBalance = $binance->getAccountBalance('USDT');

            // Calculate total PnL from the trades we're analyzing
            $totalPnl = $trades->sum('pnl');

            // Starting equity = Current balance - Total PnL from these trades
            $startingEquity = $currentBalance - $totalPnl;

            // Ensure we have a positive starting equity (safety check)
            return max($startingEquity, 100); // Minimum $100 to avoid division by zero
        } catch (\Exception $e) {
            \Log::error('Failed to get starting equity from Binance: ' . $e->getMessage());

            // If we can't get real balance, calculate from trades only
            // This is a fallback - still better than hardcoded value
            $totalPnl = $trades->sum('pnl');

            // Assume starting equity based on oldest trade entry value
            $oldestTrade = $trades->first();
            if ($oldestTrade) {
                return max($oldestTrade->margin * 10, 100); // Estimate based on margin used
            }

            throw new \RuntimeException('Cannot calculate starting equity - no real data available: ' . $e->getMessage());
        }
    }

    public function updatedSelectedPeriod(): void
    {
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.dashboard.advanced-metrics');
    }
}
