<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TradeResource;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TradeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Trade::query()
            ->with('aiDecision')
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by symbol
        if ($request->has('symbol')) {
            $query->where('symbol', $request->input('symbol'));
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $trades = $query->paginate($request->input('per_page', 50));

        return TradeResource::collection($trades);
    }

    public function show(Trade $trade): TradeResource
    {
        $trade->load('aiDecision');

        return new TradeResource($trade);
    }
}
