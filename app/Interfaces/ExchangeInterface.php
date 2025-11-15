<?php

namespace App\Interfaces;

interface ExchangeInterface
{
    /**
     * Get current market price for a symbol
     */
    public function getCurrentPrice(string $symbol): float;

    /**
     * Get OHLCV data for a symbol and timeframe
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array;

    /**
     * Place a market order
     */
    public function placeMarketOrder(
        string $symbol,
        string $side, // 'BUY' or 'SELL'
        float $quantity,
        int $leverage = 1
    ): array;

    /**
     * Place a limit order
     */
    public function placeLimitOrder(
        string $symbol,
        string $side,
        float $quantity,
        float $price,
        int $leverage = 1
    ): array;

    /**
     * Close a position
     */
    public function closePosition(string $symbol, float $quantity, string $side): array;

    /**
     * Get account balance
     */
    public function getBalance(): array;

    /**
     * Get open positions
     */
    public function getOpenPositions(): array;

    /**
     * Set stop loss for a position
     */
    public function setStopLoss(string $symbol, float $stopPrice, string $side): array;

    /**
     * Set take profit for a position
     */
    public function setTakeProfit(string $symbol, float $takeProfitPrice, string $side): array;

    /**
     * Get order status
     */
    public function getOrderStatus(string $symbol, string $orderId): array;
}
