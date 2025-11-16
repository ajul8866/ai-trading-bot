<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerformanceResource;
use App\Models\PerformanceSnapshot;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class PerformanceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $period = $request->input('period', 'hourly');

        $snapshots = PerformanceSnapshot::where('period', $period)
            ->orderBy('snapshot_at', 'desc')
            ->limit($request->input('limit', 24))
            ->get();

        return PerformanceResource::collection($snapshots);
    }

    public function metrics(): JsonResponse
    {
        return Cache::remember('performance_metrics', 60, function () {
            // Optimized: Single query instead of 6 separate queries
            $metrics = Trade::where('status', 'CLOSED')
                ->selectRaw('
                    COUNT(*) as total_trades,
                    SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                    SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losing_trades,
                    SUM(pnl) as total_pnl,
                    AVG(CASE WHEN pnl > 0 THEN pnl ELSE NULL END) as avg_win,
                    AVG(CASE WHEN pnl < 0 THEN pnl ELSE NULL END) as avg_loss
                ')
                ->first();

            $totalTrades = $metrics->total_trades ?? 0;
            $winningTrades = $metrics->winning_trades ?? 0;
            $losingTrades = $metrics->losing_trades ?? 0;
            $totalPnl = $metrics->total_pnl ?? 0;
            $avgWin = $metrics->avg_win ?? 0;
            $avgLoss = $metrics->avg_loss ?? 0;

            $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

            // Get latest snapshot for advanced metrics
            $latestSnapshot = PerformanceSnapshot::latest('snapshot_at')->first();

            return response()->json([
                'total_trades' => $totalTrades,
                'winning_trades' => $winningTrades,
                'losing_trades' => $losingTrades,
                'total_pnl' => round($totalPnl, 2),
                'win_rate' => round($winRate, 2),
                'avg_win' => round($avgWin, 2),
                'avg_loss' => round($avgLoss, 2),
                'profit_factor' => $avgLoss != 0 ? round(abs($avgWin / $avgLoss), 2) : 0,
                'sharpe_ratio' => $latestSnapshot?->sharpe_ratio,
                'sortino_ratio' => $latestSnapshot?->sortino_ratio,
                'max_drawdown' => $latestSnapshot?->max_drawdown,
            ]);
        });
    }
}
