<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiDecision extends Model
{
    /** @use HasFactory<\Database\Factories\AiDecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframes_analyzed',
        'market_conditions',
        'decision',
        'confidence',
        'reasoning',
        'risk_assessment',
        'recommended_leverage',
        'recommended_stop_loss',
        'recommended_take_profit',
        'executed',
        'execution_error',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'timeframes_analyzed' => 'array',
            'market_conditions' => 'array',
            'risk_assessment' => 'array',
            'confidence' => 'decimal:2',
            'recommended_leverage' => 'integer',
            'recommended_stop_loss' => 'decimal:8',
            'recommended_take_profit' => 'decimal:8',
            'executed' => 'boolean',
            'analyzed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship to Trades
     */
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }
}
