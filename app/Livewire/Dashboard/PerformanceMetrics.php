<?php

namespace App\Livewire\Dashboard;

use App\Models\Trade;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class PerformanceMetrics extends Component
{
    public array $metrics = [];

    public bool $isLoading = true;

    public function mount()
    {
        $this->loadMetrics();
    }

    #[On('refresh-performance')]
    public function loadMetrics()
    {
        $this->isLoading = true;

        // Cache metrics for 1 minute
        $this->metrics = Cache::remember('dashboard_performance_metrics', 60, function () {
            return $this->calculateMetrics();
        });

        $this->isLoading = false;
    }

    private function calculateMetrics(): array
    {
        $closedTrades = Trade::where('status', 'CLOSED')->get();
        $openTrades = Trade::where('status', 'OPEN')->get();

        $totalTrades = $closedTrades->count();
        $winningTrades = $closedTrades->where('pnl', '>', 0)->count();
        $losingTrades = $closedTrades->where('pnl', '<', 0)->count();

        $totalPnl = $closedTrades->sum('pnl');
        $grossProfit = $closedTrades->where('pnl', '>', 0)->sum('pnl');
        $grossLoss = abs($closedTrades->where('pnl', '<', 0)->sum('pnl'));

        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : 0;

        // Today's stats
        $todayPnl = Trade::where('status', 'CLOSED')
            ->whereDate('closed_at', today())
            ->sum('pnl');

        $todayTrades = Trade::where('status', 'CLOSED')
            ->whereDate('closed_at', today())
            ->count();

        // This week stats
        $weekPnl = Trade::where('status', 'CLOSED')
            ->whereBetween('closed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('pnl');

        // This month stats
        $monthPnl = Trade::where('status', 'CLOSED')
            ->whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->sum('pnl');

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'losing_trades' => $losingTrades,
            'win_rate' => $winRate,
            'total_pnl' => $totalPnl,
            'gross_profit' => $grossProfit,
            'gross_loss' => $grossLoss,
            'profit_factor' => $profitFactor,
            'open_positions' => $openTrades->count(),
            'today_pnl' => $todayPnl,
            'today_trades' => $todayTrades,
            'week_pnl' => $weekPnl,
            'month_pnl' => $monthPnl,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.performance-metrics');
    }
}
