<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Models\Trade;
use App\Services\BinanceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class PositionController extends Controller
{
    public function index(BinanceService $binance): AnonymousResourceCollection
    {
        // Get open positions from database
        $openTrades = Trade::where('status', 'OPEN')
            ->with('aiDecision')
            ->get();

        // Enrich with current market data for unrealized P&L
        $positions = $openTrades->map(function ($trade) use ($binance) {
            $cacheKey = "price:{$trade->symbol}";

            $currentPrice = Cache::remember($cacheKey, 5, function () use ($binance, $trade) {
                return $binance->getCurrentPrice($trade->symbol);
            });

            $unrealizedPnl = 0;
            if ($currentPrice > 0) {
                if ($trade->side === 'BUY' || $trade->side === 'LONG') {
                    $unrealizedPnl = ($currentPrice - $trade->entry_price) * $trade->quantity;
                } else {
                    $unrealizedPnl = ($trade->entry_price - $currentPrice) * $trade->quantity;
                }
            }

            $trade->current_price = $currentPrice;
            $trade->unrealized_pnl = $unrealizedPnl;
            $trade->unrealized_pnl_percentage = $trade->entry_price > 0
                ? ($unrealizedPnl / ($trade->entry_price * $trade->quantity)) * 100
                : 0;

            return $trade;
        });

        return PositionResource::collection($positions);
    }
}
