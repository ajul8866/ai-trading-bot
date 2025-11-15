<?php

namespace App\Interfaces;

use App\DTOs\MarketAnalysisDTO;
use App\DTOs\TradingDecisionDTO;

interface AIServiceInterface
{
    /**
     * Analyze market data and make a trading decision
     */
    public function analyzeAndDecide(MarketAnalysisDTO $marketData): TradingDecisionDTO;

    /**
     * Get AI model name
     */
    public function getModelName(): string;
}
