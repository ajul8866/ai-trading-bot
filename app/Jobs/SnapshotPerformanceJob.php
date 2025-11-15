<?php

namespace App\Jobs;

use App\Models\PerformanceSnapshot;
use App\Models\Trade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SnapshotPerformanceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $period = 'hourly'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Determine the time range based on period
            $startTime = $this->period === 'daily'
                ? now()->subDay()
                : now()->subHour();

            // Get all closed trades in the period
            $trades = Trade::where('status', 'CLOSED')
                ->where('closed_at', '>=', $startTime)
                ->get();

            if ($trades->isEmpty()) {
                Log::info('No trades to snapshot', ['period' => $this->period]);

                return;
            }

            $totalTrades = $trades->count();
            $winningTrades = $trades->where('pnl', '>', 0)->count();
            $totalPnl = $trades->sum('pnl');
            $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

            $winners = $trades->where('pnl', '>', 0);
            $losers = $trades->where('pnl', '<', 0);

            $avgWin = $winners->count() > 0 ? $winners->avg('pnl') : 0;
            $avgLoss = $losers->count() > 0 ? abs($losers->avg('pnl')) : 0;

            // Calculate Sharpe Ratio (simplified)
            $returns = $trades->pluck('pnl_percentage')->toArray();
            $sharpeRatio = $this->calculateSharpeRatio($returns);

            // Calculate Sortino Ratio
            $sortinoRatio = $this->calculateSortinoRatio($returns);

            // Calculate Max Drawdown
            $maxDrawdown = $this->calculateMaxDrawdown($trades);

            // Create snapshot
            $snapshot = PerformanceSnapshot::create([
                'snapshot_at' => now(),
                'period' => $this->period,
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'total_pnl' => $totalPnl,
                'sharpe_ratio' => $sharpeRatio,
                'sortino_ratio' => $sortinoRatio,
                'max_drawdown' => $maxDrawdown,
                'win_rate' => $winRate,
                'avg_win' => $avgWin,
                'avg_loss' => $avgLoss,
            ]);

            // Cache the latest snapshot
            Cache::put("performance_snapshot:{$this->period}", $snapshot, now()->addMinutes(5));

            Log::info('Performance snapshot created', [
                'period' => $this->period,
                'total_trades' => $totalTrades,
                'total_pnl' => $totalPnl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create performance snapshot', [
                'period' => $this->period,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate Sharpe Ratio
     */
    private function calculateSharpeRatio(array $returns): ?float
    {
        if (count($returns) < 2) {
            return null;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);

        if ($stdDev == 0) {
            return null;
        }

        // Assuming risk-free rate is 0 for crypto
        return $avgReturn / $stdDev;
    }

    /**
     * Calculate Sortino Ratio (only considers downside deviation)
     */
    private function calculateSortinoRatio(array $returns): ?float
    {
        if (count($returns) < 2) {
            return null;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $downsideReturns = array_filter($returns, fn ($r) => $r < 0);

        if (empty($downsideReturns)) {
            return null;
        }

        $downsideDeviation = $this->calculateStdDev($downsideReturns);

        if ($downsideDeviation == 0) {
            return null;
        }

        return $avgReturn / $downsideDeviation;
    }

    /**
     * Calculate Maximum Drawdown
     */
    private function calculateMaxDrawdown($trades): float
    {
        $cumulativePnl = 0;
        $peak = 0;
        $maxDrawdown = 0;

        foreach ($trades->sortBy('closed_at') as $trade) {
            $cumulativePnl += $trade->pnl;

            if ($cumulativePnl > $peak) {
                $peak = $cumulativePnl;
            }

            $drawdown = $peak - $cumulativePnl;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return $maxDrawdown;
    }

    /**
     * Calculate Standard Deviation
     */
    private function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }
}
