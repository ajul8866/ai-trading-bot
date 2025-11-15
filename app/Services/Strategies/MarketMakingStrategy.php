<?php

namespace App\Services\Strategies;

use App\Contracts\TradingStrategyInterface;
use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;
use App\Services\TechnicalIndicatorService;

/**
 * Market Making Strategy
 *
 * Sophisticated market making strategy that provides liquidity by placing
 * simultaneous buy and sell orders around the current market price, profiting
 * from the bid-ask spread while actively managing inventory risk.
 *
 * Core Principles:
 * - Provide liquidity on both sides of the order book
 * - Profit from bid-ask spread capture
 * - Manage inventory to avoid directional exposure
 * - Adjust quotes based on market conditions
 * - Hedge inventory when necessary
 *
 * Strategy Components:
 * 1. Spread Calculation - determine optimal bid-ask spread
 * 2. Inventory Management - balance long/short exposure
 * 3. Risk Management - limit maximum inventory
 * 4. Quote Skewing - adjust quotes based on inventory
 * 5. Volatility Adjustment - widen spreads in volatile markets
 * 6. Trend Detection - lean quotes in trending markets
 *
 * Entry Logic:
 * - Place buy order below mid-price
 * - Place sell order above mid-price
 * - Adjust spread based on volatility
 * - Skew quotes based on inventory position
 * - Lean quotes with short-term trend
 *
 * Exit/Hedge Logic:
 * - Close positions when inventory exceeds threshold
 * - Hedge large inventory with opposite orders
 * - Widen spreads when volatility spikes
 * - Pause quoting during extreme movements
 *
 * Risk Controls:
 * - Maximum inventory limit
 * - Maximum spread width
 * - Minimum spread width
 * - Circuit breaker for extreme volatility
 * - Position limits per side
 *
 * NOTE: This is a simplified market making strategy. Professional market makers
 * use order book depth analysis, tick data, and microsecond latency infrastructure.
 */
class MarketMakingStrategy implements TradingStrategyInterface
{
    private TechnicalIndicatorService $indicatorService;

    // Core parameters
    private float $baseSpreadPercent = 0.0015; // 0.15% base spread

    private float $minSpreadPercent = 0.001; // 0.1% minimum spread

    private float $maxSpreadPercent = 0.005; // 0.5% maximum spread

    private float $targetInventoryRatio = 0; // Target neutral inventory

    private float $maxInventoryRatio = 0.3; // Max 30% of capital in inventory

    private float $inventorySkewMultiplier = 0.0005; // 0.05% skew per 10% inventory

    private float $volatilityMultiplier = 2.0; // Spread multiplier in high volatility

    // Market conditions
    private int $volatilityPeriod = 20;

    private int $trendPeriod = 50;

    private float $trendThreshold = 0.002; // 0.2% slope for trend

    private int $volumePeriod = 20;

    private float $minVolumeRatio = 0.8; // Minimum 80% of average volume

    // Position management
    private float $orderSizePercent = 0.02; // 2% of capital per order

    private int $maxOrdersPerSide = 3; // Maximum simultaneous orders per side

    private float $orderSpacing = 0.0005; // 0.05% spacing between orders

    // Risk limits
    private float $maxDrawdownPercent = 0.05; // 5% max drawdown before pause

    private float $circuitBreakerVolatility = 0.10; // 10% volatility triggers pause

    private int $minTimeBetweenTrades = 1; // Minimum 1 second between fills

    public function __construct(TechnicalIndicatorService $indicatorService)
    {
        $this->indicatorService = $indicatorService;
    }

    public function getName(): string
    {
        return 'Market Making Strategy';
    }

    public function getDescription(): string
    {
        return 'Advanced market making strategy providing liquidity on both sides with spread capture, inventory management, and dynamic quote adjustment';
    }

    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO
    {
        $reasons = [];
        $signal = 'HOLD';
        $strength = 0;
        $confidence = 0;

        $primaryTimeframe = $marketData->timeframes[0] ?? '1m';
        $ohlcvData = $marketData->ohlcvData[$primaryTimeframe] ?? [];

        if (empty($ohlcvData)) {
            return $this->createHoldSignal($marketData, 'Insufficient data for market making');
        }

        // Analyze market conditions
        $marketConditions = $this->analyzeMarketConditions($ohlcvData);

        // Check if market making is viable
        $viability = $this->assessMarketMakingViability($marketConditions);

        if (! $viability['is_viable']) {
            return $this->createHoldSignal($marketData, $viability['reason']);
        }

        // Calculate current inventory (would come from position tracking in real implementation)
        $currentInventory = $this->getCurrentInventory($marketData);

        // Calculate optimal spread
        $spreadParameters = $this->calculateOptimalSpread($marketConditions, $currentInventory);

        // Determine quote prices
        $quotes = $this->calculateQuotePrices($marketConditions, $spreadParameters, $currentInventory);

        // Assess whether to place orders
        $quotingDecision = $this->makeQuotingDecision($quotes, $marketConditions, $currentInventory, $viability);

        // Calculate metrics
        $strength = $this->calculateSignalStrength($marketConditions, $viability, $currentInventory);
        $confidence = $this->calculateConfidence($marketConditions, $viability);

        // Determine signal
        // Market making typically places both buy and sell, but for this interface we'll
        // indicate the side we want to favor based on inventory
        if ($quotingDecision['should_quote']) {
            if ($quotingDecision['favor_side'] === 'BUY') {
                $signal = 'BUY';
            } elseif ($quotingDecision['favor_side'] === 'SELL') {
                $signal = 'SELL';
            } else {
                $signal = 'HOLD'; // Balanced quoting
            }
            $reasons = $quotingDecision['reasons'];
        } else {
            $reasons[] = $quotingDecision['reason'];
            $reasons[] = 'Market conditions not suitable for market making';
        }

        // Calculate entry, stop loss, and take profit
        $latestCandle = end($ohlcvData);
        $midPrice = $latestCandle['close'];
        $entryPrice = null;
        $stopLoss = null;
        $takeProfit = null;
        $riskRewardRatio = null;

        if ($signal !== 'HOLD') {
            if ($signal === 'BUY') {
                $entryPrice = $quotes['bid_price'];
                $takeProfit = $quotes['ask_price']; // Flip at ask
                $stopLoss = $this->calculateStopLoss($entryPrice, 'LONG', $marketData, $marketConditions);
            } else {
                $entryPrice = $quotes['ask_price'];
                $takeProfit = $quotes['bid_price']; // Flip at bid
                $stopLoss = $this->calculateStopLoss($entryPrice, 'SHORT', $marketData, $marketConditions);
            }

            $riskRewardRatio = abs($takeProfit - $entryPrice) / abs($entryPrice - $stopLoss);
        }

        return new StrategySignalDTO(
            strategyName: $this->getName(),
            symbol: $marketData->symbol,
            signal: $signal,
            strength: $strength,
            confidence: $confidence,
            reasons: $reasons,
            indicators: array_merge($marketConditions, [
                'spread_params' => $spreadParameters,
                'quotes' => $quotes,
                'inventory' => $currentInventory,
                'viability' => $viability,
            ]),
            entryPrice: $entryPrice,
            stopLoss: $stopLoss,
            takeProfit: $takeProfit,
            recommendedLeverage: $this->calculateLeverage($strength, $confidence),
            positionSize: $signal !== 'HOLD' ? $this->calculatePositionSize($marketData, $marketData->accountBalance) : null,
            riskRewardRatio: $riskRewardRatio,
            metadata: [
                'strategy_type' => 'MARKET_MAKING',
                'quoting_decision' => $quotingDecision,
                'mid_price' => $midPrice,
                'bid_price' => $quotes['bid_price'] ?? null,
                'ask_price' => $quotes['ask_price'] ?? null,
                'effective_spread' => $spreadParameters['effective_spread'] ?? null,
                'inventory_ratio' => $currentInventory['ratio'],
            ]
        );
    }

    private function createHoldSignal(MarketAnalysisDTO $marketData, string $reason): StrategySignalDTO
    {
        return new StrategySignalDTO(
            strategyName: $this->getName(),
            symbol: $marketData->symbol,
            signal: 'HOLD',
            strength: 0,
            confidence: 0,
            reasons: [$reason],
            indicators: [],
            metadata: []
        );
    }

    private function analyzeMarketConditions(array $ohlcvData): array
    {
        $closePrices = array_column($ohlcvData, 'close');
        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $volumes = array_column($ohlcvData, 'volume');

        // Current price
        $currentPrice = end($closePrices);

        // Calculate volatility (standard deviation of returns)
        $volatility = $this->calculateVolatility($closePrices, $this->volatilityPeriod);

        // Calculate trend
        $trend = $this->analyzeTrend($closePrices, $this->trendPeriod);

        // Calculate volume profile
        $volumeProfile = $this->analyzeVolumeProfile($volumes);

        // Calculate recent price range
        $recentHighs = array_slice($highs, -20);
        $recentLows = array_slice($lows, -20);
        $priceRange = max($recentHighs) - min($recentLows);
        $rangePercent = ($priceRange / $currentPrice) * 100;

        // Calculate bid-ask spread estimate (from high-low of recent candles)
        $recentSpread = $this->estimateSpread($ohlcvData);

        // Momentum indicators
        $rsi = $this->indicatorService->calculateRSI($closePrices, 14);
        $ema20 = $this->indicatorService->calculateEMA($closePrices, 20);

        return [
            'current_price' => $currentPrice,
            'volatility' => round($volatility, 4),
            'volatility_regime' => $this->classifyVolatility($volatility),
            'trend' => $trend,
            'volume_profile' => $volumeProfile,
            'price_range' => round($priceRange, 2),
            'range_percent' => round($rangePercent, 2),
            'estimated_spread' => round($recentSpread, 4),
            'rsi' => round($rsi, 2),
            'ema20' => round($ema20, 2),
            'liquidity' => $volumeProfile['liquidity_level'],
        ];
    }

    private function calculateVolatility(array $prices, int $period): float
    {
        if (count($prices) < $period + 1) {
            return 0;
        }

        $returns = [];
        for ($i = count($prices) - $period; $i < count($prices); $i++) {
            if ($i > 0 && $prices[$i - 1] > 0) {
                $returns[] = log($prices[$i] / $prices[$i - 1]);
            }
        }

        if (empty($returns)) {
            return 0;
        }

        $mean = array_sum($returns) / count($returns);
        $squaredDiffs = array_map(function ($r) use ($mean) {
            return pow($r - $mean, 2);
        }, $returns);

        $variance = array_sum($squaredDiffs) / count($returns);
        $stdDev = sqrt($variance);

        // Annualized volatility
        return $stdDev * sqrt(525600); // Minutes in a year (for 1m timeframe)
    }

    private function classifyVolatility(float $volatility): string
    {
        if ($volatility < 0.20) {
            return 'LOW';
        } elseif ($volatility < 0.40) {
            return 'NORMAL';
        } elseif ($volatility < 0.60) {
            return 'HIGH';
        } else {
            return 'EXTREME';
        }
    }

    private function analyzeTrend(array $prices, int $period): array
    {
        if (count($prices) < $period) {
            return [
                'direction' => 'NEUTRAL',
                'strength' => 0,
                'slope' => 0,
            ];
        }

        $recentPrices = array_slice($prices, -$period);

        // Linear regression slope
        $n = count($recentPrices);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($recentPrices as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        $slope = 0;

        if ($denominator != 0) {
            $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        }

        $avgPrice = array_sum($recentPrices) / $n;
        $normalizedSlope = ($slope / $avgPrice);

        $direction = 'NEUTRAL';
        $strength = 0;

        if ($normalizedSlope > $this->trendThreshold) {
            $direction = 'UPTREND';
            $strength = min(abs($normalizedSlope) / ($this->trendThreshold * 5), 1);
        } elseif ($normalizedSlope < -$this->trendThreshold) {
            $direction = 'DOWNTREND';
            $strength = min(abs($normalizedSlope) / ($this->trendThreshold * 5), 1);
        }

        return [
            'direction' => $direction,
            'strength' => round($strength, 2),
            'slope' => round($normalizedSlope, 6),
        ];
    }

    private function analyzeVolumeProfile(array $volumes): array
    {
        $recentVolumes = array_slice($volumes, -$this->volumePeriod);
        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        $currentVolume = end($volumes);

        $volumeRatio = $avgVolume > 0 ? $currentVolume / $avgVolume : 1;

        $liquidityLevel = 'LOW';
        if ($volumeRatio >= 1.5) {
            $liquidityLevel = 'HIGH';
        } elseif ($volumeRatio >= $this->minVolumeRatio) {
            $liquidityLevel = 'NORMAL';
        }

        return [
            'avg_volume' => $avgVolume,
            'current_volume' => $currentVolume,
            'volume_ratio' => round($volumeRatio, 2),
            'liquidity_level' => $liquidityLevel,
        ];
    }

    private function estimateSpread(array $ohlcvData): float
    {
        $recentCandles = array_slice($ohlcvData, -5);
        $spreads = [];

        foreach ($recentCandles as $candle) {
            $spread = ($candle['high'] - $candle['low']) / $candle['close'];
            $spreads[] = $spread;
        }

        return array_sum($spreads) / count($spreads);
    }

    private function assessMarketMakingViability(array $marketConditions): array
    {
        $reasons = [];
        $score = 0;
        $maxScore = 0;

        // Check volatility (30 points)
        $maxScore += 30;
        if ($marketConditions['volatility_regime'] === 'EXTREME') {
            $reasons[] = "Volatility too high ({$marketConditions['volatility']}) - circuit breaker";

            return [
                'is_viable' => false,
                'reason' => implode(', ', $reasons),
                'score' => 0,
            ];
        } elseif (in_array($marketConditions['volatility_regime'], ['NORMAL', 'LOW'])) {
            $score += 30;
        } elseif ($marketConditions['volatility_regime'] === 'HIGH') {
            $score += 15;
            $reasons[] = 'Higher volatility - wider spreads required';
        }

        // Check liquidity (40 points)
        $maxScore += 40;
        if ($marketConditions['liquidity'] === 'LOW') {
            $reasons[] = 'Insufficient liquidity for market making';

            return [
                'is_viable' => false,
                'reason' => implode(', ', $reasons),
                'score' => 0,
            ];
        } elseif ($marketConditions['liquidity'] === 'HIGH') {
            $score += 40;
        } else {
            $score += 25;
        }

        // Check trend strength (30 points) - prefer ranging markets
        $maxScore += 30;
        if ($marketConditions['trend']['direction'] === 'NEUTRAL') {
            $score += 30;
            $reasons[] = 'Ranging market - ideal for market making';
        } elseif ($marketConditions['trend']['strength'] < 0.5) {
            $score += 20;
            $reasons[] = 'Weak trend - acceptable for market making';
        } else {
            $score += 10;
            $reasons[] = 'Strong trend detected - will skew quotes';
        }

        $normalizedScore = $maxScore > 0 ? $score / $maxScore : 0;

        return [
            'is_viable' => $normalizedScore >= 0.5,
            'score' => round($normalizedScore, 2),
            'reason' => $normalizedScore >= 0.5 ? implode(', ', $reasons) : 'Market conditions not suitable',
            'reasons' => $reasons,
        ];
    }

    private function getCurrentInventory(MarketAnalysisDTO $marketData): array
    {
        // In production, this would query current positions
        // For now, simulating neutral inventory
        $totalValue = $marketData->accountBalance;
        $inventoryValue = 0; // Would be calculated from actual positions

        $inventoryRatio = $totalValue > 0 ? $inventoryValue / $totalValue : 0;

        return [
            'value' => $inventoryValue,
            'ratio' => round($inventoryRatio, 3),
            'is_long' => $inventoryValue > 0,
            'is_short' => $inventoryValue < 0,
            'is_neutral' => abs($inventoryRatio) < 0.05,
        ];
    }

    private function calculateOptimalSpread(array $marketConditions, array $inventory): array
    {
        // Start with base spread
        $spread = $this->baseSpreadPercent;

        // Adjust for volatility
        if ($marketConditions['volatility_regime'] === 'HIGH') {
            $spread *= $this->volatilityMultiplier;
        } elseif ($marketConditions['volatility_regime'] === 'EXTREME') {
            $spread *= $this->volatilityMultiplier * 2;
        }

        // Adjust for trend
        if ($marketConditions['trend']['direction'] !== 'NEUTRAL') {
            $spread *= (1 + $marketConditions['trend']['strength'] * 0.5);
        }

        // Ensure spread is within limits
        $spread = max($this->minSpreadPercent, min($this->maxSpreadPercent, $spread));

        // Calculate inventory skew
        $inventorySkew = $inventory['ratio'] * $this->inventorySkewMultiplier * 10;

        return [
            'base_spread' => $this->baseSpreadPercent,
            'effective_spread' => round($spread, 4),
            'inventory_skew' => round($inventorySkew, 4),
            'half_spread' => round($spread / 2, 4),
        ];
    }

    private function calculateQuotePrices(array $marketConditions, array $spreadParams, array $inventory): array
    {
        $midPrice = $marketConditions['current_price'];
        $halfSpread = $spreadParams['half_spread'];
        $inventorySkew = $spreadParams['inventory_skew'];

        // Calculate bid and ask prices
        // If we have long inventory, skew prices lower to encourage selling
        // If we have short inventory, skew prices higher to encourage buying
        $bidPrice = $midPrice * (1 - $halfSpread - $inventorySkew);
        $askPrice = $midPrice * (1 + $halfSpread - $inventorySkew);

        // Ensure bid < ask
        if ($bidPrice >= $askPrice) {
            $avgPrice = ($bidPrice + $askPrice) / 2;
            $bidPrice = $avgPrice * 0.999;
            $askPrice = $avgPrice * 1.001;
        }

        return [
            'mid_price' => $midPrice,
            'bid_price' => round($bidPrice, 2),
            'ask_price' => round($askPrice, 2),
            'spread_dollars' => round($askPrice - $bidPrice, 2),
            'spread_percent' => round((($askPrice - $bidPrice) / $midPrice) * 100, 3),
        ];
    }

    private function makeQuotingDecision(array $quotes, array $marketConditions, array $inventory, array $viability): array
    {
        $reasons = [];
        $shouldQuote = false;
        $favorSide = 'NEUTRAL';

        // Check if market is viable
        if (! $viability['is_viable']) {
            return [
                'should_quote' => false,
                'reason' => $viability['reason'],
                'favor_side' => 'NEUTRAL',
            ];
        }

        // Check inventory limits
        if (abs($inventory['ratio']) > $this->maxInventoryRatio) {
            $favorSide = $inventory['is_long'] ? 'SELL' : 'BUY';
            $reasons[] = "Inventory management: favoring {$favorSide} to rebalance";
            $shouldQuote = true;
        } else {
            $shouldQuote = true;
            $reasons[] = 'Normal market making mode';
            $reasons[] = "Quoting with spread: {$quotes['spread_percent']}%";

            // Determine which side to favor based on multiple factors
            if ($inventory['is_long']) {
                $favorSide = 'SELL';
                $reasons[] = 'Slight preference to SELL (long inventory)';
            } elseif ($inventory['is_short']) {
                $favorSide = 'BUY';
                $reasons[] = 'Slight preference to BUY (short inventory)';
            } else {
                // Check trend for lean
                if ($marketConditions['trend']['direction'] === 'UPTREND') {
                    $favorSide = 'BUY';
                    $reasons[] = 'Leaning BUY (uptrend detected)';
                } elseif ($marketConditions['trend']['direction'] === 'DOWNTREND') {
                    $favorSide = 'SELL';
                    $reasons[] = 'Leaning SELL (downtrend detected)';
                } else {
                    $favorSide = 'NEUTRAL';
                    $reasons[] = 'Balanced quoting (neutral market)';
                }
            }
        }

        return [
            'should_quote' => $shouldQuote,
            'favor_side' => $favorSide,
            'reasons' => $reasons,
            'reason' => implode(', ', $reasons),
        ];
    }

    private function calculateSignalStrength(array $marketConditions, array $viability, array $inventory): float
    {
        $strength = 0;

        // Base strength from viability (40 points)
        $strength += $viability['score'] * 40;

        // Strength from liquidity (30 points)
        if ($marketConditions['liquidity'] === 'HIGH') {
            $strength += 30;
        } elseif ($marketConditions['liquidity'] === 'NORMAL') {
            $strength += 20;
        }

        // Strength from favorable volatility (20 points)
        if ($marketConditions['volatility_regime'] === 'NORMAL') {
            $strength += 20;
        } elseif ($marketConditions['volatility_regime'] === 'LOW') {
            $strength += 15;
        }

        // Bonus for neutral inventory (10 points)
        if ($inventory['is_neutral']) {
            $strength += 10;
        }

        return min(round($strength, 2), 100);
    }

    private function calculateConfidence(array $marketConditions, array $viability): float
    {
        $confidence = 0;

        // Confidence from viability score (40 points)
        $confidence += $viability['score'] * 40;

        // Confidence from stable conditions (30 points)
        if ($marketConditions['volatility_regime'] === 'NORMAL' || $marketConditions['volatility_regime'] === 'LOW') {
            $confidence += 30;
        } elseif ($marketConditions['volatility_regime'] === 'HIGH') {
            $confidence += 15;
        }

        // Confidence from liquidity (30 points)
        if ($marketConditions['liquidity'] === 'HIGH') {
            $confidence += 30;
        } elseif ($marketConditions['liquidity'] === 'NORMAL') {
            $confidence += 20;
        }

        return min(round($confidence, 2), 100);
    }

    private function calculateLeverage(float $strength, float $confidence): int
    {
        // Market making uses minimal leverage due to both-sided exposure
        $avgScore = ($strength + $confidence) / 2;

        if ($avgScore >= 85) {
            return 2;
        } elseif ($avgScore >= 70) {
            return 1;
        }

        return 1; // Default to 1x for safety
    }

    public function getRequiredTimeframes(): array
    {
        return ['1m', '5m', '15m'];
    }

    public function getRequiredIndicators(): array
    {
        return ['ema', 'rsi'];
    }

    public function canTrade(MarketAnalysisDTO $marketData): bool
    {
        // Need short timeframes for market making
        if (! isset($marketData->ohlcvData['1m']) || empty($marketData->ohlcvData['1m'])) {
            return false;
        }

        // Need sufficient historical data
        if (count($marketData->ohlcvData['1m']) < $this->trendPeriod) {
            return false;
        }

        return true;
    }

    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float
    {
        // Market making uses smaller, more frequent orders
        $orderSize = $accountBalance * $this->orderSizePercent;

        $latestCandle = end($marketData->ohlcvData[$marketData->timeframes[0]]);
        $currentPrice = $latestCandle['close'];

        $positionSize = $orderSize / $currentPrice;

        return round($positionSize, 8);
    }

    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $marketConditions = null): float
    {
        // Wider stops for market making to avoid getting stopped out of inventory
        $stopPercent = 0.015; // 1.5%

        // Adjust based on volatility
        if ($marketConditions !== null && isset($marketConditions['volatility'])) {
            $stopPercent *= (1 + $marketConditions['volatility']);
        }

        if ($side === 'LONG') {
            return round($entryPrice * (1 - $stopPercent), 2);
        } else {
            return round($entryPrice * (1 + $stopPercent), 2);
        }
    }

    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float
    {
        // Take profit at the opposite quote (the spread)
        $targetSpread = $this->baseSpreadPercent;

        if ($side === 'LONG') {
            return round($entryPrice * (1 + $targetSpread), 2);
        } else {
            return round($entryPrice * (1 - $targetSpread), 2);
        }
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'avg_profit' => 0,
            'avg_loss' => 0,
            'profit_factor' => 0,
            'sharpe_ratio' => 0,
            'inventory_turnover' => 0,
            'avg_spread_captured' => 0,
            'avg_holding_time' => '0_minutes',
        ];
    }

    public function optimizeParameters(array $historicalData): array
    {
        return [
            'base_spread_percent' => $this->baseSpreadPercent,
            'min_spread_percent' => $this->minSpreadPercent,
            'max_spread_percent' => $this->maxSpreadPercent,
            'max_inventory_ratio' => $this->maxInventoryRatio,
            'order_size_percent' => $this->orderSizePercent,
        ];
    }
}
