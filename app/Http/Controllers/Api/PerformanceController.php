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
            $totalTrades = Trade::where('status', 'CLOSED')->count();
            $winningTrades = Trade::where('status', 'CLOSED')->where('pnl', '>', 0)->count();
            $losingTrades = Trade::where('status', 'CLOSED')->where('pnl', '<', 0)->count();
            $totalPnl = Trade::where('status', 'CLOSED')->sum('pnl');

            $avgWin = $winningTrades > 0
                ? Trade::where('status', 'CLOSED')->where('pnl', '>', 0)->avg('pnl')
                : 0;

            $avgLoss = $losingTrades > 0
                ? Trade::where('status', 'CLOSED')->where('pnl', '<', 0)->avg('pnl')
                : 0;

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
