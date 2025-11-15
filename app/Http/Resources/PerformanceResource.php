<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'snapshot_at' => $this->snapshot_at?->toIso8601String(),
            'period' => $this->period,
            'total_trades' => $this->total_trades,
            'winning_trades' => $this->winning_trades,
            'total_pnl' => (float) $this->total_pnl,
            'sharpe_ratio' => $this->sharpe_ratio ? (float) $this->sharpe_ratio : null,
            'sortino_ratio' => $this->sortino_ratio ? (float) $this->sortino_ratio : null,
            'max_drawdown' => $this->max_drawdown ? (float) $this->max_drawdown : null,
            'win_rate' => (float) $this->win_rate,
            'avg_win' => (float) $this->avg_win,
            'avg_loss' => (float) $this->avg_loss,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
