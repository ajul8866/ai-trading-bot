<?php

namespace App\Contracts;

use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;

/**
 * Interface TradingStrategyInterface
 *
 * Defines the contract for all trading strategies
 * Each strategy must implement this interface to be used by the bot
 */
interface TradingStrategyInterface
{
    /**
     * Get the name of the strategy
     */
    public function getName(): string;

    /**
     * Get the description of the strategy
     */
    public function getDescription(): string;

    /**
     * Analyze market data and generate trading signal
     *
     * @param MarketAnalysisDTO $marketData
     * @return StrategySignalDTO
     */
    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO;

    /**
     * Get the required timeframes for this strategy
     *
     * @return array<string>
     */
    public function getRequiredTimeframes(): array;

    /**
     * Get the required indicators for this strategy
     *
     * @return array<string>
     */
    public function getRequiredIndicators(): array;

    /**
     * Validate if the strategy can be used with given market conditions
     *
     * @param MarketAnalysisDTO $marketData
     * @return bool
     */
    public function canTrade(MarketAnalysisDTO $marketData): bool;

    /**
     * Calculate position size recommendation
     *
     * @param MarketAnalysisDTO $marketData
     * @param float $accountBalance
     * @return float
     */
    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float;

    /**
     * Calculate stop loss price
     *
     * @param float $entryPrice
     * @param string $side
     * @param MarketAnalysisDTO $marketData
     * @return float
     */
    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float;

    /**
     * Calculate take profit price
     *
     * @param float $entryPrice
     * @param string $side
     * @param MarketAnalysisDTO $marketData
     * @return float
     */
    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float;

    /**
     * Get strategy performance metrics
     *
     * @return array
     */
    public function getPerformanceMetrics(): array;

    /**
     * Optimize strategy parameters
     *
     * @param array $historicalData
     * @return array
     */
    public function optimizeParameters(array $historicalData): array;
}
