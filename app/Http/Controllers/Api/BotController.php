<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class BotController extends Controller
{
    public function status(): JsonResponse
    {
        $botEnabled = Setting::where('key', 'bot_enabled')->value('value') === 'true';

        $stats = [
            'enabled' => $botEnabled,
            'total_trades' => Trade::count(),
            'open_positions' => Trade::where('status', 'OPEN')->count(),
            'total_pnl' => Trade::where('status', 'CLOSED')->sum('pnl'),
            'win_rate' => $this->calculateWinRate(),
        ];

        return response()->json($stats);
    }

    public function start(): JsonResponse
    {
        Setting::where('key', 'bot_enabled')->update(['value' => 'true']);

        // Dispatch the bot start command in the background
        Artisan::call('bot:start');

        return response()->json([
            'message' => 'Trading bot started successfully',
            'status' => 'running',
        ]);
    }

    public function stop(): JsonResponse
    {
        Setting::where('key', 'bot_enabled')->update(['value' => 'false']);

        // Dispatch the bot stop command
        Artisan::call('bot:stop');

        return response()->json([
            'message' => 'Trading bot stopped successfully',
            'status' => 'stopped',
        ]);
    }

    private function calculateWinRate(): float
    {
        $totalTrades = Trade::where('status', 'CLOSED')->count();

        if ($totalTrades === 0) {
            return 0.0;
        }

        $winningTrades = Trade::where('status', 'CLOSED')
            ->where('pnl', '>', 0)
            ->count();

        return round(($winningTrades / $totalTrades) * 100, 2);
    }
}
