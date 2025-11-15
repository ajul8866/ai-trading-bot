<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'entry_price' => (float) $this->entry_price,
            'current_price' => (float) ($this->current_price ?? 0),
            'quantity' => (float) $this->quantity,
            'leverage' => $this->leverage,
            'stop_loss' => (float) $this->stop_loss,
            'take_profit' => (float) $this->take_profit,
            'unrealized_pnl' => (float) ($this->unrealized_pnl ?? 0),
            'unrealized_pnl_percentage' => (float) ($this->unrealized_pnl_percentage ?? 0),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'duration' => $this->opened_at?->diffForHumans(),
            'ai_decision' => $this->whenLoaded('aiDecision', function () {
                return [
                    'action' => $this->aiDecision->action,
                    'confidence' => $this->aiDecision->confidence,
                ];
            }),
        ];
    }
}
