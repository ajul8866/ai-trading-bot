<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChartDataResource;
use App\Jobs\CacheChartDataJob;
use App\Models\ChartData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ChartController extends Controller
{
    public function show(string $symbol, Request $request): AnonymousResourceCollection
    {
        $timeframe = $request->input('timeframe', '5m');
        $limit = $request->input('limit', 100);
        $cacheKey = "chart_data:{$symbol}:{$timeframe}";

        // Try to get from cache first
        $chartData = Cache::get($cacheKey);

        if (! $chartData) {
            // Dispatch job to fetch and cache data
            dispatch(new CacheChartDataJob($symbol, $timeframe, $limit));

            // Get from database
            $chartData = ChartData::where('symbol', $symbol)
                ->where('timeframe', $timeframe)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();
        }

        return ChartDataResource::collection($chartData);
    }
}
