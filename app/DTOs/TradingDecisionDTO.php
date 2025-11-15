<?php

namespace App\DTOs;

class TradingDecisionDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $decision, // 'BUY', 'SELL', 'HOLD', 'CLOSE'
        public readonly float $confidence, // 0-100
        public readonly string $reasoning,
        public readonly array $marketConditions,
        public readonly ?int $recommendedLeverage = null,
        public readonly ?float $recommendedStopLoss = null,
        public readonly ?float $recommendedTakeProfit = null,
        public readonly ?array $riskAssessment = null,
    ) {}

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'decision' => $this->decision,
            'confidence' => $this->confidence,
            'reasoning' => $this->reasoning,
            'market_conditions' => $this->marketConditions,
            'recommended_leverage' => $this->recommendedLeverage,
            'recommended_stop_loss' => $this->recommendedStopLoss,
            'recommended_take_profit' => $this->recommendedTakeProfit,
            'risk_assessment' => $this->riskAssessment,
        ];
    }

    public function shouldExecute(float $minConfidence = 70): bool
    {
        return $this->confidence >= $minConfidence && in_array($this->decision, ['BUY', 'SELL', 'CLOSE']);
    }
}
