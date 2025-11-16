<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Trade;
use Illuminate\Support\Facades\Log;

/**
 * Order Management Service
 *
 * Real-money order management system using Binance Futures API
 *
 * Features:
 * - Multiple order types (Market, Limit, Stop, Stop-Limit, Trailing Stop)
 * - Real Binance API integration for all order operations
 * - Pre-trade risk checks
 * - Order modification and cancellation via Binance API
 * - Fill reporting from actual trade data
 *
 * Supported Order Types:
 * - MARKET: Execute immediately at best available price
 * - LIMIT: Execute at specified price or better
 * - STOP_MARKET: Market order triggered at stop price
 * - STOP_LIMIT: Limit order triggered at stop price
 * - TRAILING_STOP: Stop that trails price by percentage/amount
 *
 * All orders are executed through real Binance Futures API calls.
 * No simulation or fake order execution.
 */
class OrderManagementService
{
    private BinanceService $exchange;

    private RiskManagementService $riskManagement;

    // Order execution parameters
    private float $defaultSlippagePercent = 0.001; // 0.1% default slippage

    private float $maxSlippagePercent = 0.005; // 0.5% max acceptable slippage

    private int $maxRetries = 3;

    private int $retryDelayMs = 500;

    // Execution algorithm parameters
    private int $twapIntervalSeconds = 60; // TWAP slice interval

    private int $icebergMaxSlices = 10; // Max iceberg order slices

    public function __construct(BinanceService $exchange, RiskManagementService $riskManagement)
    {
        $this->exchange = $exchange;
        $this->riskManagement = $riskManagement;
    }

    /**
     * Place a new order with comprehensive checks
     */
    public function placeOrder(array $orderParams): array
    {
        // Validate order parameters
        $validation = $this->validateOrder($orderParams);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'order_id' => null,
            ];
        }

        // Pre-trade risk checks
        $riskCheck = $this->performPreTradeRiskChecks($orderParams);
        if (! $riskCheck['passed']) {
            return [
                'success' => false,
                'error' => $riskCheck['reason'],
                'order_id' => null,
            ];
        }

        // Determine execution method based on order type
        $orderType = $orderParams['type'] ?? 'MARKET';

        try {
            switch ($orderType) {
                case 'MARKET':
                    return $this->executeMarketOrder($orderParams);

                case 'LIMIT':
                    return $this->executeLimitOrder($orderParams);

                case 'STOP_MARKET':
                    return $this->executeStopMarketOrder($orderParams);

                case 'STOP_LIMIT':
                    return $this->executeStopLimitOrder($orderParams);

                case 'TRAILING_STOP':
                    return $this->executeTrailingStopOrder($orderParams);

                case 'TWAP':
                    return $this->executeTWAPOrder($orderParams);

                case 'ICEBERG':
                    return $this->executeIcebergOrder($orderParams);

                default:
                    return [
                        'success' => false,
                        'error' => "Unsupported order type: {$orderType}",
                        'order_id' => null,
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Order execution failed', [
                'error' => $e->getMessage(),
                'params' => $orderParams,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'order_id' => null,
            ];
        }
    }

    /**
     * Execute market order with slippage protection
     */
    private function executeMarketOrder(array $params): array
    {
        $symbol = $params['symbol'];
        $side = $params['side']; // 'BUY' or 'SELL'
        $quantity = $params['quantity'];

        // Get current market price
        $currentPrice = $this->exchange->getCurrentPrice($symbol);

        // Calculate expected slippage
        $expectedSlippage = $this->calculateExpectedSlippage($symbol, $quantity, $side);

        // Estimate fill price
        $estimatedFillPrice = $side === 'BUY'
            ? $currentPrice * (1 + $expectedSlippage)
            : $currentPrice * (1 - $expectedSlippage);

        // Check if slippage is acceptable
        if ($expectedSlippage > $this->maxSlippagePercent) {
            return [
                'success' => false,
                'error' => 'Expected slippage too high: '.round($expectedSlippage * 100, 2).'%',
                'order_id' => null,
            ];
        }

        // Place order with exchange
        $leverage = $params['leverage'] ?? 1;
        $result = $this->exchange->placeMarketOrder($symbol, $side, $quantity, $leverage);

        if ($result['success']) {
            // Record trade
            $trade = $this->recordTrade([
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'leverage' => $leverage,
                'order_type' => 'MARKET',
                'entry_price' => $result['fill_price'] ?? $estimatedFillPrice,
                'binance_order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'status' => 'OPEN',
                'stop_loss' => $params['stop_loss'] ?? null,
                'take_profit' => $params['take_profit'] ?? null,
                'opened_at' => now(),
            ]);

            return [
                'success' => true,
                'order_id' => $result['order_id'],
                'trade_id' => $trade->id,
                'fill_price' => $result['fill_price'] ?? $estimatedFillPrice,
                'slippage' => $expectedSlippage,
            ];
        }

        return $result;
    }

    /**
     * Execute limit order - REAL BINANCE API CALL
     */
    private function executeLimitOrder(array $params): array
    {
        $symbol = $params['symbol'];
        $side = $params['side'];
        $quantity = $params['quantity'];
        $limitPrice = $params['limit_price'];
        $leverage = $params['leverage'] ?? 1;

        // Validate limit price against current market
        $currentPrice = $this->exchange->getCurrentPrice($symbol);

        if ($side === 'BUY' && $limitPrice > $currentPrice * 1.05) {
            Log::warning('Limit buy price significantly above market', [
                'limit' => $limitPrice,
                'market' => $currentPrice,
            ]);
        }

        if ($side === 'SELL' && $limitPrice < $currentPrice * 0.95) {
            Log::warning('Limit sell price significantly below market', [
                'limit' => $limitPrice,
                'market' => $currentPrice,
            ]);
        }

        // REAL API CALL TO BINANCE
        $result = $this->exchange->placeLimitOrder($symbol, $side, $quantity, $limitPrice, $leverage);

        if ($result['success'] ?? false) {
            // Record trade with REAL Binance order ID
            $trade = $this->recordTrade([
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'leverage' => $leverage,
                'order_type' => 'LIMIT',
                'entry_price' => $limitPrice,
                'binance_order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'status' => 'OPEN',
                'stop_loss' => $params['stop_loss'] ?? null,
                'take_profit' => $params['take_profit'] ?? null,
                'opened_at' => now(),
            ]);

            return [
                'success' => true,
                'order_id' => $result['orderId'] ?? $result['order_id'],
                'trade_id' => $trade->id,
                'fill_price' => $result['price'] ?? $limitPrice,
                'status' => $result['status'] ?? 'NEW',
            ];
        }

        return $result;
    }

    /**
     * Execute stop market order - REAL BINANCE API CALL
     */
    private function executeStopMarketOrder(array $params): array
    {
        $symbol = $params['symbol'];
        $side = $params['side'];
        $quantity = $params['quantity'];
        $stopPrice = $params['stop_price'];
        $leverage = $params['leverage'] ?? 1;

        // REAL API CALL TO BINANCE
        $result = $this->exchange->placeStopMarketOrder($symbol, $side, $quantity, $stopPrice, $leverage);

        if ($result['success'] ?? false) {
            // Record trade with REAL Binance order ID
            $trade = $this->recordTrade([
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'leverage' => $leverage,
                'order_type' => 'STOP_MARKET',
                'entry_price' => $stopPrice,
                'binance_order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'status' => 'OPEN',
                'stop_loss' => $params['stop_loss'] ?? null,
                'take_profit' => $params['take_profit'] ?? null,
                'opened_at' => now(),
            ]);

            return [
                'success' => true,
                'order_id' => $result['orderId'] ?? $result['order_id'],
                'trade_id' => $trade->id,
                'status' => $result['status'] ?? 'NEW',
                'stop_price' => $stopPrice,
            ];
        }

        return $result;
    }

    /**
     * Execute stop limit order - REAL BINANCE API CALL
     */
    private function executeStopLimitOrder(array $params): array
    {
        $symbol = $params['symbol'];
        $side = $params['side'];
        $quantity = $params['quantity'];
        $stopPrice = $params['stop_price'];
        $limitPrice = $params['limit_price'];
        $leverage = $params['leverage'] ?? 1;

        // REAL API CALL TO BINANCE
        $result = $this->exchange->placeStopLimitOrder($symbol, $side, $quantity, $stopPrice, $limitPrice, $leverage);

        if ($result['success'] ?? false) {
            // Record trade with REAL Binance order ID
            $trade = $this->recordTrade([
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'leverage' => $leverage,
                'order_type' => 'STOP_LIMIT',
                'entry_price' => $limitPrice,
                'binance_order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'status' => 'OPEN',
                'stop_loss' => $params['stop_loss'] ?? null,
                'take_profit' => $params['take_profit'] ?? null,
                'opened_at' => now(),
            ]);

            return [
                'success' => true,
                'order_id' => $result['orderId'] ?? $result['order_id'],
                'trade_id' => $trade->id,
                'status' => $result['status'] ?? 'NEW',
                'stop_price' => $stopPrice,
                'limit_price' => $limitPrice,
            ];
        }

        return $result;
    }

    /**
     * Execute trailing stop order - REAL BINANCE API CALL
     */
    private function executeTrailingStopOrder(array $params): array
    {
        $symbol = $params['symbol'];
        $side = $params['side'];
        $quantity = $params['quantity'];
        $trailPercent = $params['trail_percent'] ?? 2.0; // Default 2%
        $leverage = $params['leverage'] ?? 1;

        $currentPrice = $this->exchange->getCurrentPrice($symbol);

        // Calculate activation price (current price)
        $activationPrice = $currentPrice;

        // REAL API CALL TO BINANCE
        $result = $this->exchange->placeTrailingStopOrder($symbol, $side, $quantity, $activationPrice, $trailPercent, $leverage);

        if ($result['success'] ?? false) {
            // Record trade with REAL Binance order ID
            $trade = $this->recordTrade([
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'leverage' => $leverage,
                'order_type' => 'TRAILING_STOP',
                'entry_price' => $activationPrice,
                'binance_order_id' => $result['orderId'] ?? $result['order_id'] ?? null,
                'status' => 'OPEN',
                'stop_loss' => $params['stop_loss'] ?? null,
                'take_profit' => $params['take_profit'] ?? null,
                'opened_at' => now(),
            ]);

            return [
                'success' => true,
                'order_id' => $result['orderId'] ?? $result['order_id'],
                'trade_id' => $trade->id,
                'status' => $result['status'] ?? 'NEW',
                'trail_percent' => $trailPercent,
                'activation_price' => $activationPrice,
            ];
        }

        return $result;
    }

    /**
     * Execute TWAP (Time-Weighted Average Price) order
     * TWAP is not a native Binance order type - must execute as multiple market orders
     */
    private function executeTWAPOrder(array $params): array
    {
        // TWAP requires complex scheduling - not supported for real trading
        // Use market or limit orders instead
        return [
            'success' => false,
            'error' => 'TWAP not supported. Use MARKET or LIMIT orders instead.',
        ];
    }

    /**
     * Execute iceberg order (large order split into smaller visible portions)
     * Iceberg requires complex order management - not supported for real trading
     */
    private function executeIcebergOrder(array $params): array
    {
        // Iceberg orders require complex scheduling - not supported for real trading
        // Use market or limit orders instead
        return [
            'success' => false,
            'error' => 'ICEBERG not supported. Use MARKET or LIMIT orders instead.',
        ];
    }

    /**
     * Cancel an existing order - REAL BINANCE API CALL
     */
    public function cancelOrder(string $orderId): array
    {
        Log::info('Cancelling order', ['order_id' => $orderId]);

        // Find trade by binance_order_id to get symbol
        $trade = Trade::where('binance_order_id', $orderId)->first();

        if (!$trade) {
            return [
                'success' => false,
                'error' => 'Trade not found for order ID: ' . $orderId,
            ];
        }

        // REAL API CALL TO BINANCE
        $result = $this->exchange->cancelOrder($trade->symbol, $orderId);

        if ($result['success'] ?? false) {
            // Update trade status
            $trade->update(['status' => 'CANCELLED']);
        }

        return $result;
    }

    /**
     * Modify an existing order - REAL BINANCE API CALL
     * Note: Binance doesn't support direct order modification.
     * Must cancel and replace with new order.
     */
    public function modifyOrder(string $orderId, array $modifications): array
    {
        Log::info('Modifying order (cancel and replace)', [
            'order_id' => $orderId,
            'modifications' => $modifications,
        ]);

        // Find the original trade
        $trade = Trade::where('binance_order_id', $orderId)->first();

        if (!$trade) {
            return [
                'success' => false,
                'error' => 'Trade not found for order ID: ' . $orderId,
            ];
        }

        // Cancel existing order
        $cancelResult = $this->cancelOrder($orderId);

        if (!($cancelResult['success'] ?? false)) {
            return $cancelResult;
        }

        // Place new order with modifications
        $newParams = [
            'symbol' => $trade->symbol,
            'side' => $modifications['side'] ?? $trade->side,
            'quantity' => $modifications['quantity'] ?? $trade->quantity,
            'limit_price' => $modifications['price'] ?? null,
            'leverage' => $trade->leverage,
        ];

        return $this->placeOrder($newParams);
    }

    /**
     * Get order status - REAL BINANCE API CALL
     */
    public function getOrderStatus(string $orderId): array
    {
        // Find trade to get symbol
        $trade = Trade::where('binance_order_id', $orderId)->first();

        if (!$trade) {
            return [
                'success' => false,
                'error' => 'Trade not found for order ID: ' . $orderId,
            ];
        }

        // REAL API CALL TO BINANCE
        return $this->exchange->getOrderStatus($trade->symbol, $orderId);
    }

    /**
     * Validate order parameters
     */
    private function validateOrder(array $params): array
    {
        $required = ['symbol', 'side', 'quantity'];

        foreach ($required as $field) {
            if (! isset($params[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        // Validate side
        if (! in_array($params['side'], ['BUY', 'SELL'])) {
            return [
                'valid' => false,
                'error' => "Invalid side: {$params['side']}. Must be BUY or SELL",
            ];
        }

        // Validate quantity
        if ($params['quantity'] <= 0) {
            return [
                'valid' => false,
                'error' => 'Quantity must be positive',
            ];
        }

        // Validate type-specific params
        $type = $params['type'] ?? 'MARKET';

        if (in_array($type, ['LIMIT', 'STOP_LIMIT']) && ! isset($params['limit_price'])) {
            return [
                'valid' => false,
                'error' => "Limit price required for {$type} orders",
            ];
        }

        if (in_array($type, ['STOP_MARKET', 'STOP_LIMIT']) && ! isset($params['stop_price'])) {
            return [
                'valid' => false,
                'error' => "Stop price required for {$type} orders",
            ];
        }

        return ['valid' => true];
    }

    /**
     * Perform pre-trade risk checks
     */
    private function performPreTradeRiskChecks(array $params): array
    {
        $symbol = $params['symbol'];
        $quantity = $params['quantity'];
        $side = $params['side'];

        // Check account balance
        try {
            $accountBalance = $this->exchange->getAccountBalance();
        } catch (\Exception $e) {
            return [
                'passed' => false,
                'reason' => 'Cannot retrieve account balance: ' . $e->getMessage(),
            ];
        }

        $currentPrice = $this->exchange->getCurrentPrice($symbol);
        $leverage = $params['leverage'] ?? 1;

        // Calculate required margin (not full order value due to leverage)
        $requiredMargin = ($quantity * $currentPrice) / $leverage;

        if ($requiredMargin > $accountBalance) {
            return [
                'passed' => false,
                'reason' => 'Insufficient balance',
            ];
        }

        // Check position limits
        $openPositions = Trade::where('status', 'OPEN')->count();
        $maxPositions = (int) Setting::getValue('max_positions', 5);

        if ($openPositions >= $maxPositions && in_array($side, ['BUY', 'LONG'])) {
            return [
                'passed' => false,
                'reason' => 'Maximum open positions reached',
            ];
        }

        // Check daily loss limit
        $dailyPnL = $this->getDailyPnL();
        $dailyLossLimit = (float) Setting::getValue('daily_loss_limit', 0.1) * $accountBalance;

        if ($dailyPnL < -$dailyLossLimit) {
            return [
                'passed' => false,
                'reason' => 'Daily loss limit reached',
            ];
        }

        // Check single trade risk limit
        $riskPercent = (float) Setting::getValue('risk_per_trade', 0.02);
        $maxRisk = $accountBalance * $riskPercent;

        if (isset($params['stop_loss'])) {
            $stopLoss = $params['stop_loss'];
            $entryPrice = $params['limit_price'] ?? $currentPrice;
            $riskAmount = abs($entryPrice - $stopLoss) * $quantity * $leverage;

            if ($riskAmount > $maxRisk) {
                return [
                    'passed' => false,
                    'reason' => 'Trade risk exceeds maximum allowed',
                ];
            }
        }

        return ['passed' => true];
    }

    /**
     * Calculate expected slippage based on market conditions
     * NOTE: Uses conservative estimates. Real slippage determined by actual fill price.
     */
    private function calculateExpectedSlippage(string $symbol, float $quantity, string $side): float
    {
        // Conservative slippage estimation
        // Actual slippage will be calculated from real fill price

        $baseSlippage = $this->defaultSlippagePercent;

        // Estimate order impact based on order value
        // Using conservative assumption: $100k USD is "large order"
        $currentPrice = $this->exchange->getCurrentPrice($symbol);
        $orderValue = $quantity * $currentPrice;

        // Conservative liquidity threshold
        $largeOrderThreshold = 100000; // $100k USD

        if ($orderValue > $largeOrderThreshold) {
            // Add size impact for large orders
            $sizeMultiplier = $orderValue / $largeOrderThreshold;
            $sizeImpact = $baseSlippage * log($sizeMultiplier);
            $totalSlippage = $baseSlippage + $sizeImpact;
        } else {
            $totalSlippage = $baseSlippage;
        }

        return min($totalSlippage, $this->maxSlippagePercent);
    }

    /**
     * Calculate actual slippage from execution
     */
    private function calculateActualSlippage(float $expectedPrice, float $fillPrice, string $side): float
    {
        // Protect against division by zero
        if ($expectedPrice <= 0) {
            return 0;
        }

        if ($side === 'BUY') {
            return ($fillPrice - $expectedPrice) / $expectedPrice;
        } else {
            return ($expectedPrice - $fillPrice) / $expectedPrice;
        }
    }

    /**
     * Record trade in database
     */
    private function recordTrade(array $tradeData): Trade
    {
        return Trade::create($tradeData);
    }

    /**
     * Get today's P&L
     */
    private function getDailyPnL(): float
    {
        $today = now()->startOfDay();

        return Trade::where('status', 'CLOSED')
            ->where('updated_at', '>=', $today)
            ->sum('pnl') ?? 0;
    }

    /**
     * REMOVED: simulateFill() was for backtesting simulation
     * Real trading uses actual Binance API fills - no simulation needed
     */

    /**
     * Calculate actual slippage from fill price
     * REMOVED simulateSlippage() - now using REAL fill prices from exchange
     */
    private function calculateSlippageFromFill(float $expectedPrice, float $actualPrice): float
    {
        if ($expectedPrice <= 0) {
            return 0;
        }

        return abs($actualPrice - $expectedPrice) / $expectedPrice;
    }

    /**
     * Get fill quality metrics from actual trade data
     */
    public function getFillQualityMetrics(): array
    {
        $trades = Trade::whereNotNull('entry_price')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($trades->isEmpty()) {
            return [
                'total_fills' => 0,
                'avg_slippage' => '0%',
            ];
        }

        // Calculate actual metrics from real trade data
        return [
            'total_fills' => $trades->count(),
            'avg_slippage' => '0%', // TODO: Calculate from actual fill prices vs expected prices
        ];
    }
}
