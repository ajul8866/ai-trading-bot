<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    /** @use HasFactory<\Database\Factories\TradeFactory> */
    use HasFactory;

    protected $fillable = [
        'symbol',
        'side',
        'entry_price',
        'exit_price',
        'quantity',
        'leverage',
        'margin',
        'order_type',
        'stop_loss',
        'take_profit',
        'status',
        'pnl',
        'pnl_percentage',
        'binance_order_id',
        'ai_decision_id',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_price' => 'decimal:8',
            'exit_price' => 'decimal:8',
            'quantity' => 'decimal:8',
            'margin' => 'decimal:8',
            'stop_loss' => 'decimal:8',
            'take_profit' => 'decimal:8',
            'pnl' => 'decimal:8',
            'pnl_percentage' => 'decimal:4',
            'leverage' => 'integer',
            'ai_decision_id' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship to AI Decision
     */
    public function aiDecision()
    {
        return $this->belongsTo(AiDecision::class);
    }
}
