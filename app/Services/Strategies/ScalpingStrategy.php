<?php

namespace App\Services\Strategies;

use App\Contracts\TradingStrategyInterface;
use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;
use App\Services\TechnicalIndicatorService;

/**
 * Scalping Strategy
 *
 * High-frequency trading strategy designed for capturing small price movements
 * in highly liquid markets. Uses micro-timeframe analysis, order flow indicators,
 * and rapid execution to profit from tiny inefficiencies.
 *
 * Core Principles:
 * - Quick in and out - hold time: seconds to minutes
 * - High win rate, small profit per trade
 * - Tight risk management with quick stops
 * - Trades in direction of micro-trends
 * - Exploits bid-ask spread dynamics
 *
 * Entry Conditions (LONG):
 * - Fast EMA (5) crosses above Slow EMA (10)
 * - Stochastic oversold and turning up
 * - Positive momentum on 1-minute chart
 * - Order flow showing buying pressure
 * - Spread is tight (liquid market)
 * - Price bouncing off micro support
 *
 * Entry Conditions (SHORT):
 * - Fast EMA (5) crosses below Slow EMA (10)
 * - Stochastic overbought and turning down
 * - Negative momentum on 1-minute chart
 * - Order flow showing selling pressure
 * - Spread is tight (liquid market)
 * - Price rejecting from micro resistance
 *
 * Exit Conditions:
 * - Target reached (0.3-0.5% profit)
 * - EMA crossover reverses
 * - Stochastic reaches opposite extreme
 * - Stop loss hit (0.2-0.3%)
 * - Time-based exit (if no movement in 5 minutes)
 *
 * Risk Management:
 * - Very tight stops (0.2-0.3% from entry)
 * - Quick profit targets (0.3-0.5%)
 * - Position sizing: 3-5% of capital
 * - Maximum 3 concurrent positions
 * - No scalping during high impact news
 * - Only in highly liquid pairs
 */
class ScalpingStrategy implements TradingStrategyInterface
{
    private TechnicalIndicatorService $indicatorService;

    // Strategy parameters
    private int $fastEMA = 5;
    private int $slowEMA = 10;
    private int $trendEMA = 20;
    private int $stochasticK = 14;
    private int $stochasticD = 3;
    private int $stochasticSmooth = 3;
    private float $stochasticOversold = 20;
    private float $stochasticOverbought = 80;
    private int $rsiPeriod = 9; // Fast RSI for scalping
    private float $rsiOversold = 30;
    private float $rsiOverbought = 70;
    private float $targetProfitPercent = 0.004; // 0.4% target
    private float $stopLossPercent = 0.003; // 0.3% stop
    private int $momentumPeriod = 10;
    private int $volumeAvgPeriod = 20;
    private float $minLiquidityRatio = 1.5; // Volume must be 1.5x average

    // Microstructure parameters
    private float $spreadThreshold = 0.001; // 0.1% max spread
    private int $orderFlowPeriod = 5; // Last 5 candles for order flow
    private float $trendStrengthThreshold = 0.6;

    public function __construct(TechnicalIndicatorService $indicatorService)
    {
        $this->indicatorService = $indicatorService;
    }

    public function getName(): string
    {
        return 'Scalping Strategy';
    }

    public function getDescription(): string
    {
        return 'High-frequency scalping strategy for quick profits from micro price movements using fast EMAs, Stochastic, and order flow analysis';
    }

    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO
    {
        $reasons = [];
        $signal = 'HOLD';
        $strength = 0;
        $confidence = 0;

        // Scalping requires very short timeframes
        $primaryTimeframe = $marketData->timeframes[0] ?? '1m';
        $ohlcvData = $marketData->ohlcvData[$primaryTimeframe] ?? [];

        if (empty($ohlcvData)) {
            return $this->createHoldSignal($marketData, 'Insufficient data for scalping');
        }

        // Check if we have minimum required candles
        if (count($ohlcvData) < max($this->trendEMA, $this->stochasticK, $this->volumeAvgPeriod)) {
            return $this->createHoldSignal($marketData, 'Not enough historical data');
        }

        // Perform micro-analysis
        $analysis = $this->performMicroAnalysis($ohlcvData);

        // Check market microstructure
        $microstructure = $this->analyzeMicrostructure($ohlcvData);

        // Analyze order flow
        $orderFlow = $this->analyzeOrderFlow($ohlcvData);

        // Check liquidity conditions
        $liquidity = $this->analyzeLiquidity($ohlcvData);

        // Analyze momentum
        $momentum = $this->analyzeMomentum($ohlcvData);

        // Check higher timeframe bias
        $higherTimeframeBias = $this->getHigherTimeframeBias($marketData);

        // Evaluate scalping opportunity
        $opportunity = $this->evaluateScalpingOpportunity(
            $analysis,
            $microstructure,
            $orderFlow,
            $liquidity,
            $momentum,
            $higherTimeframeBias
        );

        // Calculate signal strength and confidence
        $strength = $this->calculateSignalStrength($opportunity, $liquidity, $momentum);
        $confidence = $this->calculateConfidence($opportunity, $microstructure, $higherTimeframeBias);

        // Determine signal
        if ($opportunity['direction'] === 'LONG' && $opportunity['quality'] >= 0.7) {
            $signal = 'BUY';
            $reasons = $opportunity['reasons'];
        } elseif ($opportunity['direction'] === 'SHORT' && $opportunity['quality'] >= 0.7) {
            $signal = 'SELL';
            $reasons = $opportunity['reasons'];
        } else {
            $reasons[] = 'No high-quality scalping opportunity';
            $reasons[] = $opportunity['status'] ?? 'Waiting for optimal conditions';
            $reasons[] = "Market microstructure: {$microstructure['condition']}";
        }

        // Calculate entry, stop loss, and take profit
        $latestCandle = end($ohlcvData);
        $entryPrice = $signal !== 'HOLD' ? $latestCandle['close'] : null;
        $stopLoss = null;
        $takeProfit = null;
        $riskRewardRatio = null;

        if ($signal === 'BUY') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'LONG', $marketData, $analysis);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'LONG', $marketData);
            $riskRewardRatio = abs($takeProfit - $entryPrice) / abs($entryPrice - $stopLoss);
        } elseif ($signal === 'SELL') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'SHORT', $marketData, $analysis);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'SHORT', $marketData);
            $riskRewardRatio = abs($entryPrice - $takeProfit) / abs($stopLoss - $entryPrice);
        }

        return new StrategySignalDTO(
            strategyName: $this->getName(),
            symbol: $marketData->symbol,
            signal: $signal,
            strength: $strength,
            confidence: $confidence,
            reasons: $reasons,
            indicators: array_merge($analysis, [
                'microstructure' => $microstructure,
                'order_flow' => $orderFlow,
                'liquidity' => $liquidity,
                'momentum' => $momentum,
            ]),
            entryPrice: $entryPrice,
            stopLoss: $stopLoss,
            takeProfit: $takeProfit,
            recommendedLeverage: $this->calculateLeverage($strength, $confidence),
            positionSize: $signal !== 'HOLD' ? $this->calculatePositionSize($marketData, $marketData->accountBalance) : null,
            riskRewardRatio: $riskRewardRatio,
            metadata: [
                'scalping_opportunity' => $opportunity,
                'higher_timeframe_bias' => $higherTimeframeBias,
                'execution_speed' => 'IMMEDIATE', // Scalping requires instant execution
                'max_hold_time' => '5_minutes',
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

    private function performMicroAnalysis(array $ohlcvData): array
    {
        $closePrices = array_column($ohlcvData, 'close');
        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');

        // Calculate fast EMAs for scalping
        $fastEMA = $this->indicatorService->calculateEMA($closePrices, $this->fastEMA);
        $slowEMA = $this->indicatorService->calculateEMA($closePrices, $this->slowEMA);
        $trendEMA = $this->indicatorService->calculateEMA($closePrices, $this->trendEMA);

        // Calculate Stochastic
        $stochastic = $this->calculateStochastic($ohlcvData, $this->stochasticK, $this->stochasticD, $this->stochasticSmooth);

        // Calculate fast RSI
        $rsi = $this->indicatorService->calculateRSI($closePrices, $this->rsiPeriod);

        // Detect EMA crossover
        $previousFast = $this->indicatorService->calculateEMA(array_slice($closePrices, 0, -1), $this->fastEMA);
        $previousSlow = $this->indicatorService->calculateEMA(array_slice($closePrices, 0, -1), $this->slowEMA);

        $bullishCross = $previousFast <= $previousSlow && $fastEMA > $slowEMA;
        $bearishCross = $previousFast >= $previousSlow && $fastEMA < $slowEMA;

        // Current price position
        $currentPrice = end($closePrices);

        // Trend direction
        $trend = 'NEUTRAL';
        if ($currentPrice > $trendEMA && $fastEMA > $slowEMA) {
            $trend = 'BULLISH';
        } elseif ($currentPrice < $trendEMA && $fastEMA < $slowEMA) {
            $trend = 'BEARISH';
        }

        return [
            'current_price' => $currentPrice,
            'fast_ema' => round($fastEMA, 2),
            'slow_ema' => round($slowEMA, 2),
            'trend_ema' => round($trendEMA, 2),
            'trend' => $trend,
            'stochastic_k' => round($stochastic['k'], 2),
            'stochastic_d' => round($stochastic['d'], 2),
            'rsi' => round($rsi, 2),
            'bullish_cross' => $bullishCross,
            'bearish_cross' => $bearishCross,
            'ema_distance' => round((($fastEMA - $slowEMA) / $slowEMA) * 100, 3),
        ];
    }

    private function calculateStochastic(array $ohlcvData, int $kPeriod, int $dPeriod, int $smooth): array
    {
        if (count($ohlcvData) < $kPeriod) {
            return ['k' => 50, 'd' => 50];
        }

        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $closes = array_column($ohlcvData, 'close');

        // Calculate %K
        $recentHighs = array_slice($highs, -$kPeriod);
        $recentLows = array_slice($lows, -$kPeriod);
        $currentClose = end($closes);

        $highestHigh = max($recentHighs);
        $lowestLow = min($recentLows);

        $k = 50;
        if ($highestHigh != $lowestLow) {
            $k = (($currentClose - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
        }

        // For simplicity, %D is SMA of %K (would need to calculate multiple %K values for accuracy)
        // In production, you'd calculate %K for each period and then smooth
        $d = $k; // Simplified

        return [
            'k' => $k,
            'd' => $d,
        ];
    }

    private function analyzeMicrostructure(array $ohlcvData): array
    {
        $length = count($ohlcvData);
        if ($length < 3) {
            return [
                'condition' => 'UNKNOWN',
                'spread' => 0,
                'candle_pattern' => 'NONE',
            ];
        }

        $latest = $ohlcvData[$length - 1];
        $previous = $ohlcvData[$length - 2];

        // Estimate spread from high-low range
        $spread = ($latest['high'] - $latest['low']) / $latest['close'];
        $isLiquid = $spread <= $this->spreadThreshold;

        // Analyze recent candle patterns
        $candlePattern = $this->identifyMicroCandlePattern($previous, $latest);

        // Analyze price action
        $priceAction = 'NEUTRAL';
        $bodySize = abs($latest['close'] - $latest['open']);
        $totalRange = $latest['high'] - $latest['low'];

        if ($totalRange > 0) {
            $bodyRatio = $bodySize / $totalRange;

            if ($bodyRatio > 0.7) {
                if ($latest['close'] > $latest['open']) {
                    $priceAction = 'STRONG_BUYING';
                } else {
                    $priceAction = 'STRONG_SELLING';
                }
            } elseif ($bodyRatio < 0.3) {
                $priceAction = 'INDECISION';
            }
        }

        $condition = $isLiquid ? 'FAVORABLE' : 'WIDE_SPREAD';

        return [
            'condition' => $condition,
            'spread' => round($spread * 100, 3),
            'is_liquid' => $isLiquid,
            'candle_pattern' => $candlePattern,
            'price_action' => $priceAction,
            'body_ratio' => round($bodyRatio ?? 0, 2),
        ];
    }

    private function identifyMicroCandlePattern(array $prev, array $curr): string
    {
        // Bullish engulfing
        if ($prev['close'] < $prev['open'] &&
            $curr['close'] > $curr['open'] &&
            $curr['open'] < $prev['close'] &&
            $curr['close'] > $prev['open']) {
            return 'BULLISH_ENGULFING';
        }

        // Bearish engulfing
        if ($prev['close'] > $prev['open'] &&
            $curr['close'] < $curr['open'] &&
            $curr['open'] > $prev['close'] &&
            $curr['close'] < $prev['open']) {
            return 'BEARISH_ENGULFING';
        }

        // Pin bar (hammer/shooting star)
        $currBody = abs($curr['close'] - $curr['open']);
        $currUpperWick = $curr['high'] - max($curr['open'], $curr['close']);
        $currLowerWick = min($curr['open'], $curr['close']) - $curr['low'];

        if ($currLowerWick > $currBody * 2 && $currUpperWick < $currBody * 0.5) {
            return 'HAMMER';
        }

        if ($currUpperWick > $currBody * 2 && $currLowerWick < $currBody * 0.5) {
            return 'SHOOTING_STAR';
        }

        return 'NONE';
    }

    private function analyzeOrderFlow(array $ohlcvData): array
    {
        $length = count($ohlcvData);
        if ($length < $this->orderFlowPeriod) {
            return [
                'direction' => 'NEUTRAL',
                'strength' => 0,
            ];
        }

        $recentCandles = array_slice($ohlcvData, -$this->orderFlowPeriod);

        $buyingPressure = 0;
        $sellingPressure = 0;

        foreach ($recentCandles as $candle) {
            $range = $candle['high'] - $candle['low'];
            if ($range == 0) continue;

            // If close is in upper half of range, buying pressure
            $closePosition = ($candle['close'] - $candle['low']) / $range;

            if ($closePosition > 0.6) {
                $buyingPressure += $candle['volume'];
            } elseif ($closePosition < 0.4) {
                $sellingPressure += $candle['volume'];
            }

            // Additional: if close > open, it's a buying candle
            if ($candle['close'] > $candle['open']) {
                $buyingPressure += $candle['volume'] * 0.5;
            } else {
                $sellingPressure += $candle['volume'] * 0.5;
            }
        }

        $totalPressure = $buyingPressure + $sellingPressure;
        $direction = 'NEUTRAL';
        $strength = 0;

        if ($totalPressure > 0) {
            $buyRatio = $buyingPressure / $totalPressure;
            $sellRatio = $sellingPressure / $totalPressure;

            if ($buyRatio > 0.6) {
                $direction = 'BUYING';
                $strength = $buyRatio;
            } elseif ($sellRatio > 0.6) {
                $direction = 'SELLING';
                $strength = $sellRatio;
            }
        }

        return [
            'direction' => $direction,
            'strength' => round($strength, 2),
            'buying_pressure' => round($buyingPressure, 2),
            'selling_pressure' => round($sellingPressure, 2),
        ];
    }

    private function analyzeLiquidity(array $ohlcvData): array
    {
        $volumes = array_column($ohlcvData, 'volume');
        $recentVolumes = array_slice($volumes, -$this->volumeAvgPeriod);

        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        $currentVolume = end($volumes);

        $volumeRatio = $avgVolume > 0 ? $currentVolume / $avgVolume : 1;
        $isLiquid = $volumeRatio >= $this->minLiquidityRatio;

        // Calculate volume trend
        $volumeTrend = 'STABLE';
        $firstHalf = array_slice($recentVolumes, 0, floor(count($recentVolumes) / 2));
        $secondHalf = array_slice($recentVolumes, floor(count($recentVolumes) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($secondAvg > $firstAvg * 1.2) {
            $volumeTrend = 'INCREASING';
        } elseif ($secondAvg < $firstAvg * 0.8) {
            $volumeTrend = 'DECREASING';
        }

        return [
            'is_liquid' => $isLiquid,
            'volume_ratio' => round($volumeRatio, 2),
            'avg_volume' => $avgVolume,
            'current_volume' => $currentVolume,
            'volume_trend' => $volumeTrend,
        ];
    }

    private function analyzeMomentum(array $ohlcvData): array
    {
        $closePrices = array_column($ohlcvData, 'close');

        if (count($closePrices) < $this->momentumPeriod + 1) {
            return [
                'value' => 0,
                'direction' => 'NEUTRAL',
                'strength' => 0,
            ];
        }

        // Calculate momentum (Rate of Change)
        $current = end($closePrices);
        $previous = $closePrices[count($closePrices) - 1 - $this->momentumPeriod];

        $momentum = (($current - $previous) / $previous) * 100;

        $direction = 'NEUTRAL';
        $strength = 0;

        if ($momentum > 0.1) {
            $direction = 'POSITIVE';
            $strength = min(abs($momentum) / 2, 100); // Normalize
        } elseif ($momentum < -0.1) {
            $direction = 'NEGATIVE';
            $strength = min(abs($momentum) / 2, 100);
        }

        return [
            'value' => round($momentum, 3),
            'direction' => $direction,
            'strength' => round($strength, 2),
        ];
    }

    private function getHigherTimeframeBias(MarketAnalysisDTO $marketData): array
    {
        $bias = [];

        // Check 5m and 15m timeframes for overall bias
        foreach (['5m', '15m'] as $timeframe) {
            if (!isset($marketData->ohlcvData[$timeframe]) || empty($marketData->ohlcvData[$timeframe])) {
                continue;
            }

            $closePrices = array_column($marketData->ohlcvData[$timeframe], 'close');
            $ema20 = $this->indicatorService->calculateEMA($closePrices, 20);
            $currentPrice = end($closePrices);

            $direction = 'NEUTRAL';
            if ($currentPrice > $ema20 * 1.002) {
                $direction = 'BULLISH';
            } elseif ($currentPrice < $ema20 * 0.998) {
                $direction = 'BEARISH';
            }

            $bias[$timeframe] = [
                'direction' => $direction,
                'ema20' => round($ema20, 2),
                'price' => $currentPrice,
            ];
        }

        // Determine overall bias
        $bullishCount = 0;
        $bearishCount = 0;

        foreach ($bias as $tf => $data) {
            if ($data['direction'] === 'BULLISH') $bullishCount++;
            if ($data['direction'] === 'BEARISH') $bearishCount++;
        }

        $overall = 'NEUTRAL';
        if ($bullishCount > $bearishCount) {
            $overall = 'BULLISH';
        } elseif ($bearishCount > $bullishCount) {
            $overall = 'BEARISH';
        }

        return [
            'timeframes' => $bias,
            'overall' => $overall,
        ];
    }

    private function evaluateScalpingOpportunity(
        array $analysis,
        array $microstructure,
        array $orderFlow,
        array $liquidity,
        array $momentum,
        array $higherTimeframeBias
    ): array {
        $opportunity = [
            'direction' => 'NONE',
            'quality' => 0,
            'reasons' => [],
            'status' => 'Evaluating...',
        ];

        // Must have favorable microstructure
        if (!$microstructure['is_liquid']) {
            $opportunity['status'] = 'Spread too wide - low liquidity';
            return $opportunity;
        }

        // Must have sufficient liquidity
        if (!$liquidity['is_liquid']) {
            $opportunity['status'] = 'Insufficient volume';
            return $opportunity;
        }

        // Evaluate LONG opportunity
        $longQuality = 0;
        $longReasons = [];

        if ($analysis['bullish_cross']) {
            $longQuality += 0.2;
            $longReasons[] = 'Fast EMA crossed above Slow EMA';
        }

        if ($analysis['stochastic_k'] < $this->stochasticOversold && $analysis['stochastic_k'] > $analysis['stochastic_d']) {
            $longQuality += 0.15;
            $longReasons[] = "Stochastic oversold and turning up (K: {$analysis['stochastic_k']})";
        }

        if ($analysis['rsi'] < $this->rsiOversold) {
            $longQuality += 0.15;
            $longReasons[] = "RSI oversold: {$analysis['rsi']}";
        }

        if ($orderFlow['direction'] === 'BUYING') {
            $longQuality += 0.2;
            $longReasons[] = "Strong buying pressure detected (strength: {$orderFlow['strength']})";
        }

        if ($momentum['direction'] === 'POSITIVE') {
            $longQuality += 0.1;
            $longReasons[] = "Positive momentum: {$momentum['value']}%";
        }

        if ($higherTimeframeBias['overall'] === 'BULLISH') {
            $longQuality += 0.15;
            $longReasons[] = 'Higher timeframes bullish';
        }

        if (in_array($microstructure['candle_pattern'], ['BULLISH_ENGULFING', 'HAMMER'])) {
            $longQuality += 0.05;
            $longReasons[] = "Bullish candle pattern: {$microstructure['candle_pattern']}";
        }

        // Evaluate SHORT opportunity
        $shortQuality = 0;
        $shortReasons = [];

        if ($analysis['bearish_cross']) {
            $shortQuality += 0.2;
            $shortReasons[] = 'Fast EMA crossed below Slow EMA';
        }

        if ($analysis['stochastic_k'] > $this->stochasticOverbought && $analysis['stochastic_k'] < $analysis['stochastic_d']) {
            $shortQuality += 0.15;
            $shortReasons[] = "Stochastic overbought and turning down (K: {$analysis['stochastic_k']})";
        }

        if ($analysis['rsi'] > $this->rsiOverbought) {
            $shortQuality += 0.15;
            $shortReasons[] = "RSI overbought: {$analysis['rsi']}";
        }

        if ($orderFlow['direction'] === 'SELLING') {
            $shortQuality += 0.2;
            $shortReasons[] = "Strong selling pressure detected (strength: {$orderFlow['strength']})";
        }

        if ($momentum['direction'] === 'NEGATIVE') {
            $shortQuality += 0.1;
            $shortReasons[] = "Negative momentum: {$momentum['value']}%";
        }

        if ($higherTimeframeBias['overall'] === 'BEARISH') {
            $shortQuality += 0.15;
            $shortReasons[] = 'Higher timeframes bearish';
        }

        if (in_array($microstructure['candle_pattern'], ['BEARISH_ENGULFING', 'SHOOTING_STAR'])) {
            $shortQuality += 0.05;
            $shortReasons[] = "Bearish candle pattern: {$microstructure['candle_pattern']}";
        }

        // Determine best opportunity
        if ($longQuality >= 0.7 && $longQuality > $shortQuality) {
            $opportunity['direction'] = 'LONG';
            $opportunity['quality'] = round($longQuality, 2);
            $opportunity['reasons'] = $longReasons;
        } elseif ($shortQuality >= 0.7 && $shortQuality > $longQuality) {
            $opportunity['direction'] = 'SHORT';
            $opportunity['quality'] = round($shortQuality, 2);
            $opportunity['reasons'] = $shortReasons;
        } else {
            $opportunity['status'] = 'No high-quality setup (Long: ' . round($longQuality, 2) . ', Short: ' . round($shortQuality, 2) . ')';
        }

        return $opportunity;
    }

    private function calculateSignalStrength(array $opportunity, array $liquidity, array $momentum): float
    {
        $strength = 0;

        if ($opportunity['direction'] === 'NONE') {
            return 0;
        }

        // Strength from opportunity quality (50 points)
        $strength += $opportunity['quality'] * 50;

        // Strength from liquidity (25 points)
        $liquidityScore = min($liquidity['volume_ratio'] / 2, 1); // Cap at 2x
        $strength += $liquidityScore * 25;

        // Strength from momentum (25 points)
        $momentumScore = min($momentum['strength'] / 100, 1);
        $strength += $momentumScore * 25;

        return min(round($strength, 2), 100);
    }

    private function calculateConfidence(array $opportunity, array $microstructure, array $higherTimeframeBias): float
    {
        $confidence = 0;

        if ($opportunity['direction'] === 'NONE') {
            return 0;
        }

        // Confidence from opportunity quality (50 points)
        $confidence += $opportunity['quality'] * 50;

        // Confidence from favorable microstructure (25 points)
        if ($microstructure['is_liquid'] && $microstructure['price_action'] !== 'INDECISION') {
            $confidence += 25;
        } elseif ($microstructure['is_liquid']) {
            $confidence += 15;
        }

        // Confidence from higher timeframe alignment (25 points)
        if ($higherTimeframeBias['overall'] === 'BULLISH' && $opportunity['direction'] === 'LONG') {
            $confidence += 25;
        } elseif ($higherTimeframeBias['overall'] === 'BEARISH' && $opportunity['direction'] === 'SHORT') {
            $confidence += 25;
        } elseif ($higherTimeframeBias['overall'] === 'NEUTRAL') {
            $confidence += 10;
        }

        return min(round($confidence, 2), 100);
    }

    private function calculateLeverage(float $strength, float $confidence): int
    {
        // Conservative leverage for scalping despite high frequency
        $avgScore = ($strength + $confidence) / 2;

        if ($avgScore >= 85) {
            return 5;
        } elseif ($avgScore >= 75) {
            return 3;
        } elseif ($avgScore >= 65) {
            return 2;
        }

        return 1;
    }

    public function getRequiredTimeframes(): array
    {
        // Scalping uses very short timeframes
        return ['1m', '5m', '15m'];
    }

    public function getRequiredIndicators(): array
    {
        return ['ema', 'rsi'];
    }

    public function canTrade(MarketAnalysisDTO $marketData): bool
    {
        // Check 1m timeframe availability (critical for scalping)
        if (!isset($marketData->ohlcvData['1m']) || empty($marketData->ohlcvData['1m'])) {
            return false;
        }

        // Need sufficient data
        if (count($marketData->ohlcvData['1m']) < $this->volumeAvgPeriod) {
            return false;
        }

        return true;
    }

    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float
    {
        // Scalping uses smaller position sizes but higher frequency
        // Risk 1% per trade (conservative for high frequency)
        $riskPercentage = 0.01;
        $riskAmount = $accountBalance * $riskPercentage;

        $latestCandle = end($marketData->ohlcvData[$marketData->timeframes[0]]);
        $currentPrice = $latestCandle['close'];

        // Stop loss distance
        $stopLossDistance = $currentPrice * $this->stopLossPercent;

        $positionSize = $riskAmount / $stopLossDistance;

        return round($positionSize, 8);
    }

    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $analysis = null): float
    {
        // Very tight stop loss for scalping
        if ($side === 'LONG') {
            return round($entryPrice * (1 - $this->stopLossPercent), 2);
        } else {
            return round($entryPrice * (1 + $this->stopLossPercent), 2);
        }
    }

    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float
    {
        // Quick profit target for scalping
        if ($side === 'LONG') {
            return round($entryPrice * (1 + $this->targetProfitPercent), 2);
        } else {
            return round($entryPrice * (1 - $this->targetProfitPercent), 2);
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
            'avg_trade_duration' => '0_minutes',
            'max_concurrent_positions' => 3,
        ];
    }

    public function optimizeParameters(array $historicalData): array
    {
        return [
            'fast_ema' => $this->fastEMA,
            'slow_ema' => $this->slowEMA,
            'stochastic_k' => $this->stochasticK,
            'target_profit_percent' => $this->targetProfitPercent,
            'stop_loss_percent' => $this->stopLossPercent,
        ];
    }
}
