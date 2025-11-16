<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TradeIndexRequest;
use App\Http\Resources\TradeResource;
use App\Models\Trade;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TradeController extends Controller
{
    public function index(TradeIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $query = Trade::query()
            ->with('aiDecision')
            ->latest('id'); // Explicit column for consistency

        // Filter by status
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by symbol
        if (isset($validated['symbol'])) {
            $query->where('symbol', $validated['symbol']);
        }

        // Filter by date range
        if (isset($validated['from'])) {
            $query->where('created_at', '>=', $validated['from']);
        }

        if (isset($validated['to'])) {
            $query->where('created_at', '<=', $validated['to']);
        }

        $trades = $query->paginate($validated['per_page'] ?? 50);

        return TradeResource::collection($trades);
    }

    public function show(Trade $trade): TradeResource
    {
        $trade->load('aiDecision');

        return new TradeResource($trade);
    }
}
