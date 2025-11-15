<?php

namespace App\DTOs;

class MarketAnalysisDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly array $timeframes, // ['5m', '15m', '30m', '1h']
        public readonly array $ohlcvData, // Multi-timeframe OHLCV data
        public readonly array $indicators, // Technical indicators
        public readonly array $openPositions, // Current open positions
        public readonly float $accountBalance,
        public readonly int $maxPositions,
        public readonly float $riskPerTrade,
        public readonly float $dailyLossLimit,
    ) {}

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'timeframes' => $this->timeframes,
            'ohlcv_data' => $this->ohlcvData,
            'indicators' => $this->indicators,
            'open_positions' => $this->openPositions,
            'account_balance' => $this->accountBalance,
            'max_positions' => $this->maxPositions,
            'risk_per_trade' => $this->riskPerTrade,
            'daily_loss_limit' => $this->dailyLossLimit,
        ];
    }
}
