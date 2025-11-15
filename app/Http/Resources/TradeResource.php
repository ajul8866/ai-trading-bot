<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'entry_price' => (float) $this->entry_price,
            'exit_price' => (float) $this->exit_price,
            'quantity' => (float) $this->quantity,
            'leverage' => $this->leverage,
            'stop_loss' => (float) $this->stop_loss,
            'take_profit' => (float) $this->take_profit,
            'status' => $this->status,
            'pnl' => (float) $this->pnl,
            'pnl_percentage' => (float) $this->pnl_percentage,
            'binance_order_id' => $this->binance_order_id,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'duration' => $this->opened_at && $this->closed_at
                ? $this->opened_at->diffForHumans($this->closed_at, true)
                : null,
            'ai_decision' => $this->whenLoaded('aiDecision', function () {
                return [
                    'action' => $this->aiDecision->action,
                    'confidence' => $this->aiDecision->confidence,
                    'reasoning' => $this->aiDecision->reasoning,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
