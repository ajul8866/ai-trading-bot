<?php

namespace App\Services\Strategies;

use App\Contracts\TradingStrategyInterface;
use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;
use App\Services\TechnicalIndicatorService;

/**
 * Mean Reversion Strategy
 *
 * This strategy exploits the tendency of prices to revert to their mean
 * after extreme movements. It uses statistical analysis and multiple
 * indicators to identify overbought/oversold conditions.
 *
 * Core Principles:
 * - Markets oscillate around a mean value
 * - Extreme deviations are temporary and will revert
 * - Uses Bollinger Bands, RSI, and Z-Score for confirmation
 * - Best in ranging/sideways markets
 *
 * Entry Conditions (LONG):
 * - Price touches or breaks below lower Bollinger Band
 * - RSI < 30 (oversold)
 * - Z-Score < -2 (statistically oversold)
 * - Volume spike (>1.5x average)
 * - Price action shows reversal patterns
 * - Multiple timeframes confirming oversold
 *
 * Entry Conditions (SHORT):
 * - Price touches or breaks above upper Bollinger Band
 * - RSI > 70 (overbought)
 * - Z-Score > 2 (statistically overbought)
 * - Volume spike (>1.5x average)
 * - Price action shows reversal patterns
 * - Multiple timeframes confirming overbought
 *
 * Exit Conditions:
 * - Price reaches mean (middle Bollinger Band)
 * - RSI returns to neutral zone (40-60)
 * - Z-Score normalizes (between -1 and 1)
 * - Stop loss hit (beyond outer band)
 * - Take profit hit (at opposite band)
 *
 * Risk Management:
 * - Smaller position sizes in trending markets
 * - Wider stops beyond statistical extremes
 * - Partial profit taking at mean
 * - Avoid trading during strong trends
 */
class MeanReversionStrategy implements TradingStrategyInterface
{
    private TechnicalIndicatorService $indicatorService;

    // Strategy parameters (optimizable)
    private int $bollingerPeriod = 20;
    private float $bollingerStdDev = 2.0;
    private int $rsiPeriod = 14;
    private float $rsiOversold = 30;
    private float $rsiOverbought = 70;
    private int $volumeAvgPeriod = 20;
    private float $volumeSpikeMultiplier = 1.5;
    private int $zScorePeriod = 20;
    private float $zScoreExtreme = 2.0;
    private float $riskRewardRatio = 1.5;
    private int $meanPeriod = 50;

    // Market regime detection
    private float $trendThreshold = 0.02; // 2% slope indicates trending
    private int $rangeDetectionPeriod = 50;

    public function __construct(TechnicalIndicatorService $indicatorService)
    {
        $this->indicatorService = $indicatorService;
    }

    public function getName(): string
    {
        return 'Mean Reversion Strategy';
    }

    public function getDescription(): string
    {
        return 'Statistical mean reversion strategy using Bollinger Bands, RSI, and Z-Score to identify extreme price deviations in ranging markets';
    }

    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO
    {
        $reasons = [];
        $signal = 'HOLD';
        $strength = 0;
        $confidence = 0;

        // Analyze primary timeframe
        $primaryTimeframe = $marketData->timeframes[0] ?? '5m';
        $ohlcvData = $marketData->ohlcvData[$primaryTimeframe] ?? [];

        if (empty($ohlcvData)) {
            return $this->createHoldSignal($marketData, 'Insufficient market data');
        }

        // Detect market regime (trending vs ranging)
        $marketRegime = $this->detectMarketRegime($ohlcvData);

        // Mean reversion works best in ranging markets
        if ($marketRegime['type'] === 'TRENDING' && $marketRegime['strength'] > 0.7) {
            return $this->createHoldSignal($marketData, 'Strong trending market detected - mean reversion not suitable');
        }

        // Calculate all indicators
        $analysis = $this->performDetailedAnalysis($ohlcvData, $marketData->indicators[$primaryTimeframe] ?? []);

        // Analyze higher timeframes for confirmation
        $higherTimeframeAnalysis = $this->analyzeHigherTimeframes($marketData);

        // Calculate signal strength and confidence
        $strength = $this->calculateSignalStrength($analysis, $higherTimeframeAnalysis, $marketRegime);
        $confidence = $this->calculateConfidence($analysis, $higherTimeframeAnalysis, $marketRegime);

        // Determine signal based on multiple confirmations
        $longConditions = $this->checkLongConditions($analysis, $higherTimeframeAnalysis);
        $shortConditions = $this->checkShortConditions($analysis, $higherTimeframeAnalysis);

        if ($longConditions['met'] && $longConditions['score'] >= 0.75) {
            $signal = 'BUY';
            $reasons = $longConditions['reasons'];
        } elseif ($shortConditions['met'] && $shortConditions['score'] >= 0.75) {
            $signal = 'SELL';
            $reasons = $shortConditions['reasons'];
        } else {
            $reasons[] = 'No extreme deviation detected';
            $reasons[] = 'Waiting for statistical extreme';
            $reasons[] = "Market regime: {$marketRegime['type']} (strength: {$marketRegime['strength']})";
        }

        // Calculate entry, stop loss, and take profit
        $latestCandle = end($ohlcvData);
        $entryPrice = $signal !== 'HOLD' ? $latestCandle['close'] : null;
        $stopLoss = null;
        $takeProfit = null;
        $riskRewardRatio = null;

        if ($signal === 'BUY') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'LONG', $marketData, $analysis);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'LONG', $marketData, $analysis);
            $riskRewardRatio = abs($takeProfit - $entryPrice) / abs($entryPrice - $stopLoss);
        } elseif ($signal === 'SELL') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'SHORT', $marketData, $analysis);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'SHORT', $marketData, $analysis);
            $riskRewardRatio = abs($entryPrice - $takeProfit) / abs($stopLoss - $entryPrice);
        }

        return new StrategySignalDTO(
            strategyName: $this->getName(),
            symbol: $marketData->symbol,
            signal: $signal,
            strength: $strength,
            confidence: $confidence,
            reasons: $reasons,
            indicators: $analysis,
            entryPrice: $entryPrice,
            stopLoss: $stopLoss,
            takeProfit: $takeProfit,
            recommendedLeverage: $this->calculateLeverage($strength, $confidence, $marketRegime),
            positionSize: $signal !== 'HOLD' ? $this->calculatePositionSize($marketData, $marketData->accountBalance) : null,
            riskRewardRatio: $riskRewardRatio,
            metadata: [
                'market_regime' => $marketRegime,
                'higher_timeframe_analysis' => $higherTimeframeAnalysis,
                'long_conditions_score' => $longConditions['score'] ?? 0,
                'short_conditions_score' => $shortConditions['score'] ?? 0,
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

    private function performDetailedAnalysis(array $ohlcvData, array $providedIndicators): array
    {
        $closePrices = array_column($ohlcvData, 'close');
        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $volumes = array_column($ohlcvData, 'volume');

        // Calculate Bollinger Bands
        $bollingerBands = $this->indicatorService->calculateBollingerBands(
            $closePrices,
            $this->bollingerPeriod,
            $this->bollingerStdDev
        );

        // Calculate RSI
        $rsi = $this->indicatorService->calculateRSI($closePrices, $this->rsiPeriod);

        // Calculate Z-Score
        $zScore = $this->calculateZScore($closePrices, $this->zScorePeriod);

        // Calculate volume metrics
        $volumeAnalysis = $this->analyzeVolume($volumes);

        // Calculate price action patterns
        $priceAction = $this->analyzePriceAction($ohlcvData);

        // Calculate mean and deviation metrics
        $meanMetrics = $this->calculateMeanMetrics($closePrices);

        // Calculate distance from bands
        $currentPrice = end($closePrices);
        $upperBand = $bollingerBands['upper'];
        $lowerBand = $bollingerBands['lower'];
        $middleBand = $bollingerBands['middle'];

        $distanceFromUpper = ($currentPrice - $upperBand) / $currentPrice;
        $distanceFromLower = ($lowerBand - $currentPrice) / $currentPrice;
        $distanceFromMean = ($currentPrice - $middleBand) / $currentPrice;

        // Calculate Bollinger Band Width (volatility)
        $bbWidth = ($upperBand - $lowerBand) / $middleBand;

        // Calculate %B (position within bands)
        $percentB = ($currentPrice - $lowerBand) / ($upperBand - $lowerBand);

        return [
            'current_price' => $currentPrice,
            'bollinger_upper' => $upperBand,
            'bollinger_middle' => $middleBand,
            'bollinger_lower' => $lowerBand,
            'bb_width' => round($bbWidth, 4),
            'percent_b' => round($percentB, 4),
            'rsi' => round($rsi, 2),
            'z_score' => round($zScore, 2),
            'distance_from_upper' => round($distanceFromUpper * 100, 2),
            'distance_from_lower' => round($distanceFromLower * 100, 2),
            'distance_from_mean' => round($distanceFromMean * 100, 2),
            'volume_spike' => $volumeAnalysis['is_spike'],
            'volume_ratio' => round($volumeAnalysis['ratio'], 2),
            'avg_volume' => $volumeAnalysis['average'],
            'price_action' => $priceAction,
            'mean_metrics' => $meanMetrics,
            'volatility_regime' => $this->classifyVolatility($bbWidth),
        ];
    }

    private function calculateZScore(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return 0;
        }

        $recentPrices = array_slice($prices, -$period);
        $currentPrice = end($prices);

        $mean = array_sum($recentPrices) / count($recentPrices);

        // Calculate standard deviation
        $squaredDiffs = array_map(function($price) use ($mean) {
            return pow($price - $mean, 2);
        }, $recentPrices);

        $variance = array_sum($squaredDiffs) / count($recentPrices);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return 0;
        }

        return ($currentPrice - $mean) / $stdDev;
    }

    private function analyzeVolume(array $volumes): array
    {
        if (empty($volumes)) {
            return [
                'is_spike' => false,
                'ratio' => 1.0,
                'average' => 0,
            ];
        }

        $recentVolumes = array_slice($volumes, -$this->volumeAvgPeriod);
        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        $currentVolume = end($volumes);

        $ratio = $avgVolume > 0 ? $currentVolume / $avgVolume : 1;
        $isSpike = $ratio >= $this->volumeSpikeMultiplier;

        return [
            'is_spike' => $isSpike,
            'ratio' => $ratio,
            'average' => $avgVolume,
            'current' => $currentVolume,
        ];
    }

    private function analyzePriceAction(array $ohlcvData): array
    {
        $length = count($ohlcvData);
        if ($length < 3) {
            return [
                'pattern' => 'UNKNOWN',
                'reversal_signal' => false,
            ];
        }

        $current = $ohlcvData[$length - 1];
        $previous = $ohlcvData[$length - 2];
        $previous2 = $ohlcvData[$length - 3];

        // Check for bullish reversal patterns
        $bullishEngulfing = $this->isBullishEngulfing($previous, $current);
        $hammer = $this->isHammer($current);
        $morningStarDoji = $this->isMorningStarDoji($previous2, $previous, $current);

        // Check for bearish reversal patterns
        $bearishEngulfing = $this->isBearishEngulfing($previous, $current);
        $shootingStar = $this->isShootingStar($current);
        $eveningStarDoji = $this->isEveningStarDoji($previous2, $previous, $current);

        $pattern = 'NONE';
        $reversalSignal = false;
        $direction = 'NEUTRAL';

        if ($bullishEngulfing || $hammer || $morningStarDoji) {
            $reversalSignal = true;
            $direction = 'BULLISH';
            if ($bullishEngulfing) $pattern = 'BULLISH_ENGULFING';
            if ($hammer) $pattern = 'HAMMER';
            if ($morningStarDoji) $pattern = 'MORNING_STAR_DOJI';
        } elseif ($bearishEngulfing || $shootingStar || $eveningStarDoji) {
            $reversalSignal = true;
            $direction = 'BEARISH';
            if ($bearishEngulfing) $pattern = 'BEARISH_ENGULFING';
            if ($shootingStar) $pattern = 'SHOOTING_STAR';
            if ($eveningStarDoji) $pattern = 'EVENING_STAR_DOJI';
        }

        return [
            'pattern' => $pattern,
            'reversal_signal' => $reversalSignal,
            'direction' => $direction,
            'body_size' => $this->getCandleBodySize($current),
            'upper_wick' => $this->getUpperWickSize($current),
            'lower_wick' => $this->getLowerWickSize($current),
        ];
    }

    private function isBullishEngulfing(array $prev, array $curr): bool
    {
        $prevBody = abs($prev['close'] - $prev['open']);
        $currBody = abs($curr['close'] - $curr['open']);

        return $prev['close'] < $prev['open'] // Previous candle bearish
            && $curr['close'] > $curr['open'] // Current candle bullish
            && $curr['open'] < $prev['close']  // Opens below previous close
            && $curr['close'] > $prev['open']  // Closes above previous open
            && $currBody > $prevBody;          // Larger body
    }

    private function isBearishEngulfing(array $prev, array $curr): bool
    {
        $prevBody = abs($prev['close'] - $prev['open']);
        $currBody = abs($curr['close'] - $curr['open']);

        return $prev['close'] > $prev['open'] // Previous candle bullish
            && $curr['close'] < $curr['open'] // Current candle bearish
            && $curr['open'] > $prev['close']  // Opens above previous close
            && $curr['close'] < $prev['open']  // Closes below previous open
            && $currBody > $prevBody;          // Larger body
    }

    private function isHammer(array $candle): bool
    {
        $body = abs($candle['close'] - $candle['open']);
        $lowerWick = min($candle['open'], $candle['close']) - $candle['low'];
        $upperWick = $candle['high'] - max($candle['open'], $candle['close']);

        return $lowerWick > ($body * 2) // Long lower wick
            && $upperWick < ($body * 0.3) // Small upper wick
            && $body > 0; // Has a body
    }

    private function isShootingStar(array $candle): bool
    {
        $body = abs($candle['close'] - $candle['open']);
        $lowerWick = min($candle['open'], $candle['close']) - $candle['low'];
        $upperWick = $candle['high'] - max($candle['open'], $candle['close']);

        return $upperWick > ($body * 2) // Long upper wick
            && $lowerWick < ($body * 0.3) // Small lower wick
            && $body > 0; // Has a body
    }

    private function isMorningStarDoji(array $first, array $second, array $third): bool
    {
        $firstBearish = $first['close'] < $first['open'];
        $secondDoji = abs($second['close'] - $second['open']) < (($second['high'] - $second['low']) * 0.1);
        $thirdBullish = $third['close'] > $third['open'];

        return $firstBearish && $secondDoji && $thirdBullish;
    }

    private function isEveningStarDoji(array $first, array $second, array $third): bool
    {
        $firstBullish = $first['close'] > $first['open'];
        $secondDoji = abs($second['close'] - $second['open']) < (($second['high'] - $second['low']) * 0.1);
        $thirdBearish = $third['close'] < $third['open'];

        return $firstBullish && $secondDoji && $thirdBearish;
    }

    private function getCandleBodySize(array $candle): float
    {
        return abs($candle['close'] - $candle['open']);
    }

    private function getUpperWickSize(array $candle): float
    {
        return $candle['high'] - max($candle['open'], $candle['close']);
    }

    private function getLowerWickSize(array $candle): float
    {
        return min($candle['open'], $candle['close']) - $candle['low'];
    }

    private function calculateMeanMetrics(array $prices): array
    {
        if (count($prices) < $this->meanPeriod) {
            return [
                'sma' => 0,
                'distance_from_sma' => 0,
                'above_mean' => false,
            ];
        }

        $recentPrices = array_slice($prices, -$this->meanPeriod);
        $sma = array_sum($recentPrices) / count($recentPrices);
        $currentPrice = end($prices);

        $distanceFromSMA = (($currentPrice - $sma) / $sma) * 100;

        return [
            'sma' => $sma,
            'distance_from_sma' => round($distanceFromSMA, 2),
            'above_mean' => $currentPrice > $sma,
        ];
    }

    private function detectMarketRegime(array $ohlcvData): array
    {
        if (count($ohlcvData) < $this->rangeDetectionPeriod) {
            return [
                'type' => 'UNKNOWN',
                'strength' => 0,
                'slope' => 0,
            ];
        }

        $closePrices = array_column($ohlcvData, 'close');
        $recentPrices = array_slice($closePrices, -$this->rangeDetectionPeriod);

        // Calculate linear regression slope
        $slope = $this->calculateLinearRegressionSlope($recentPrices);
        $normalizedSlope = abs($slope) / (array_sum($recentPrices) / count($recentPrices));

        // Calculate price range
        $high = max($recentPrices);
        $low = min($recentPrices);
        $range = ($high - $low) / $low;

        // Determine regime
        $type = 'RANGING';
        $strength = 0;

        if ($normalizedSlope > $this->trendThreshold) {
            $type = 'TRENDING';
            $strength = min($normalizedSlope / $this->trendThreshold, 1);
        } else {
            $strength = 1 - ($normalizedSlope / $this->trendThreshold);
        }

        return [
            'type' => $type,
            'strength' => round($strength, 2),
            'slope' => round($slope, 6),
            'range' => round($range * 100, 2),
        ];
    }

    private function calculateLinearRegressionSlope(array $prices): float
    {
        $n = count($prices);
        if ($n < 2) return 0;

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($prices as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0) return 0;

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function classifyVolatility(float $bbWidth): string
    {
        if ($bbWidth < 0.02) {
            return 'LOW';
        } elseif ($bbWidth < 0.04) {
            return 'NORMAL';
        } elseif ($bbWidth < 0.06) {
            return 'HIGH';
        } else {
            return 'EXTREME';
        }
    }

    private function analyzeHigherTimeframes(MarketAnalysisDTO $marketData): array
    {
        $analysis = [];

        foreach ($marketData->timeframes as $timeframe) {
            $ohlcvData = $marketData->ohlcvData[$timeframe] ?? [];

            if (empty($ohlcvData)) {
                continue;
            }

            $closePrices = array_column($ohlcvData, 'close');
            $bollingerBands = $this->indicatorService->calculateBollingerBands($closePrices, $this->bollingerPeriod, $this->bollingerStdDev);
            $rsi = $this->indicatorService->calculateRSI($closePrices, $this->rsiPeriod);
            $currentPrice = end($closePrices);

            $percentB = ($currentPrice - $bollingerBands['lower']) / ($bollingerBands['upper'] - $bollingerBands['lower']);

            $condition = 'NEUTRAL';
            if ($percentB < 0.2 && $rsi < $this->rsiOversold) {
                $condition = 'OVERSOLD';
            } elseif ($percentB > 0.8 && $rsi > $this->rsiOverbought) {
                $condition = 'OVERBOUGHT';
            }

            $analysis[$timeframe] = [
                'percent_b' => round($percentB, 2),
                'rsi' => round($rsi, 2),
                'condition' => $condition,
            ];
        }

        return $analysis;
    }

    private function checkLongConditions(array $analysis, array $higherTimeframeAnalysis): array
    {
        $score = 0;
        $maxScore = 0;
        $reasons = [];

        // Check primary conditions
        // 1. Price near lower band (weight: 25%)
        $maxScore += 25;
        if ($analysis['percent_b'] < 0.2) {
            $score += 25;
            $reasons[] = "Price near lower Bollinger Band (%B: {$analysis['percent_b']})";
        } elseif ($analysis['percent_b'] < 0.3) {
            $score += 15;
            $reasons[] = "Price approaching lower Bollinger Band (%B: {$analysis['percent_b']})";
        }

        // 2. RSI oversold (weight: 20%)
        $maxScore += 20;
        if ($analysis['rsi'] < $this->rsiOversold) {
            $score += 20;
            $reasons[] = "RSI oversold: {$analysis['rsi']}";
        } elseif ($analysis['rsi'] < 35) {
            $score += 10;
            $reasons[] = "RSI approaching oversold: {$analysis['rsi']}";
        }

        // 3. Z-Score extreme (weight: 20%)
        $maxScore += 20;
        if ($analysis['z_score'] < -$this->zScoreExtreme) {
            $score += 20;
            $reasons[] = "Extreme negative Z-Score: {$analysis['z_score']} (statistical oversold)";
        } elseif ($analysis['z_score'] < -1.5) {
            $score += 10;
            $reasons[] = "Negative Z-Score: {$analysis['z_score']}";
        }

        // 4. Volume spike (weight: 15%)
        $maxScore += 15;
        if ($analysis['volume_spike']) {
            $score += 15;
            $reasons[] = "Volume spike detected ({$analysis['volume_ratio']}x average)";
        }

        // 5. Bullish reversal pattern (weight: 10%)
        $maxScore += 10;
        if ($analysis['price_action']['reversal_signal'] && $analysis['price_action']['direction'] === 'BULLISH') {
            $score += 10;
            $reasons[] = "Bullish reversal pattern: {$analysis['price_action']['pattern']}";
        }

        // 6. Higher timeframe confirmation (weight: 10%)
        $maxScore += 10;
        $oversoldCount = 0;
        $totalTimeframes = count($higherTimeframeAnalysis);
        foreach ($higherTimeframeAnalysis as $tf => $data) {
            if ($data['condition'] === 'OVERSOLD') {
                $oversoldCount++;
            }
        }
        if ($totalTimeframes > 0) {
            $oversoldRatio = $oversoldCount / $totalTimeframes;
            $score += $oversoldRatio * 10;
            if ($oversoldRatio >= 0.5) {
                $reasons[] = "Higher timeframes confirming oversold ({$oversoldCount}/{$totalTimeframes})";
            }
        }

        $normalizedScore = $maxScore > 0 ? $score / $maxScore : 0;

        return [
            'met' => $normalizedScore >= 0.75,
            'score' => round($normalizedScore, 2),
            'reasons' => $reasons,
        ];
    }

    private function checkShortConditions(array $analysis, array $higherTimeframeAnalysis): array
    {
        $score = 0;
        $maxScore = 0;
        $reasons = [];

        // Check primary conditions
        // 1. Price near upper band (weight: 25%)
        $maxScore += 25;
        if ($analysis['percent_b'] > 0.8) {
            $score += 25;
            $reasons[] = "Price near upper Bollinger Band (%B: {$analysis['percent_b']})";
        } elseif ($analysis['percent_b'] > 0.7) {
            $score += 15;
            $reasons[] = "Price approaching upper Bollinger Band (%B: {$analysis['percent_b']})";
        }

        // 2. RSI overbought (weight: 20%)
        $maxScore += 20;
        if ($analysis['rsi'] > $this->rsiOverbought) {
            $score += 20;
            $reasons[] = "RSI overbought: {$analysis['rsi']}";
        } elseif ($analysis['rsi'] > 65) {
            $score += 10;
            $reasons[] = "RSI approaching overbought: {$analysis['rsi']}";
        }

        // 3. Z-Score extreme (weight: 20%)
        $maxScore += 20;
        if ($analysis['z_score'] > $this->zScoreExtreme) {
            $score += 20;
            $reasons[] = "Extreme positive Z-Score: {$analysis['z_score']} (statistical overbought)";
        } elseif ($analysis['z_score'] > 1.5) {
            $score += 10;
            $reasons[] = "Positive Z-Score: {$analysis['z_score']}";
        }

        // 4. Volume spike (weight: 15%)
        $maxScore += 15;
        if ($analysis['volume_spike']) {
            $score += 15;
            $reasons[] = "Volume spike detected ({$analysis['volume_ratio']}x average)";
        }

        // 5. Bearish reversal pattern (weight: 10%)
        $maxScore += 10;
        if ($analysis['price_action']['reversal_signal'] && $analysis['price_action']['direction'] === 'BEARISH') {
            $score += 10;
            $reasons[] = "Bearish reversal pattern: {$analysis['price_action']['pattern']}";
        }

        // 6. Higher timeframe confirmation (weight: 10%)
        $maxScore += 10;
        $overboughtCount = 0;
        $totalTimeframes = count($higherTimeframeAnalysis);
        foreach ($higherTimeframeAnalysis as $tf => $data) {
            if ($data['condition'] === 'OVERBOUGHT') {
                $overboughtCount++;
            }
        }
        if ($totalTimeframes > 0) {
            $overboughtRatio = $overboughtCount / $totalTimeframes;
            $score += $overboughtRatio * 10;
            if ($overboughtRatio >= 0.5) {
                $reasons[] = "Higher timeframes confirming overbought ({$overboughtCount}/{$totalTimeframes})";
            }
        }

        $normalizedScore = $maxScore > 0 ? $score / $maxScore : 0;

        return [
            'met' => $normalizedScore >= 0.75,
            'score' => round($normalizedScore, 2),
            'reasons' => $reasons,
        ];
    }

    private function calculateSignalStrength(array $analysis, array $higherTimeframeAnalysis, array $marketRegime): float
    {
        $strength = 0;

        // Strength from deviation extremes (40 points)
        $deviationScore = 0;
        $deviationScore += min(abs($analysis['z_score']) / 3 * 15, 15); // Z-Score contribution
        $deviationScore += (abs($analysis['percent_b'] - 0.5) / 0.5) * 15; // %B contribution
        $deviationScore += min(abs($analysis['rsi'] - 50) / 50 * 10, 10); // RSI contribution
        $strength += $deviationScore;

        // Strength from market regime (30 points)
        if ($marketRegime['type'] === 'RANGING') {
            $strength += $marketRegime['strength'] * 30;
        }

        // Strength from volume (15 points)
        if ($analysis['volume_spike']) {
            $strength += min($analysis['volume_ratio'] * 5, 15);
        }

        // Strength from price action (15 points)
        if ($analysis['price_action']['reversal_signal']) {
            $strength += 15;
        }

        return min(round($strength, 2), 100);
    }

    private function calculateConfidence(array $analysis, array $higherTimeframeAnalysis, array $marketRegime): float
    {
        $confidence = 0;

        // Confidence from market regime suitability (35 points)
        if ($marketRegime['type'] === 'RANGING') {
            $confidence += $marketRegime['strength'] * 35;
        }

        // Confidence from statistical extremes (30 points)
        if (abs($analysis['z_score']) > 2) {
            $confidence += 20;
        } elseif (abs($analysis['z_score']) > 1.5) {
            $confidence += 10;
        }
        if ($analysis['percent_b'] < 0.2 || $analysis['percent_b'] > 0.8) {
            $confidence += 10;
        }

        // Confidence from higher timeframe alignment (25 points)
        $extremeCount = 0;
        foreach ($higherTimeframeAnalysis as $data) {
            if ($data['condition'] !== 'NEUTRAL') {
                $extremeCount++;
            }
        }
        $alignmentRatio = count($higherTimeframeAnalysis) > 0 ? $extremeCount / count($higherTimeframeAnalysis) : 0;
        $confidence += $alignmentRatio * 25;

        // Confidence from clear signals (10 points)
        if ($analysis['price_action']['reversal_signal'] && $analysis['volume_spike']) {
            $confidence += 10;
        }

        return min(round($confidence, 2), 100);
    }

    private function calculateLeverage(float $strength, float $confidence, array $marketRegime): int
    {
        $avgScore = ($strength + $confidence) / 2;

        // Reduce leverage in trending markets
        if ($marketRegime['type'] === 'TRENDING') {
            $avgScore *= 0.7;
        }

        if ($avgScore >= 85) {
            return 3; // Conservative max leverage for mean reversion
        } elseif ($avgScore >= 75) {
            return 2;
        }

        return 1;
    }

    public function getRequiredTimeframes(): array
    {
        return ['5m', '15m', '30m', '1h'];
    }

    public function getRequiredIndicators(): array
    {
        return ['bollinger', 'rsi'];
    }

    public function canTrade(MarketAnalysisDTO $marketData): bool
    {
        foreach ($this->getRequiredTimeframes() as $timeframe) {
            if (!isset($marketData->ohlcvData[$timeframe]) || empty($marketData->ohlcvData[$timeframe])) {
                return false;
            }

            // Need minimum data points for calculations
            if (count($marketData->ohlcvData[$timeframe]) < max($this->bollingerPeriod, $this->zScorePeriod, $this->rangeDetectionPeriod)) {
                return false;
            }
        }

        return true;
    }

    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float
    {
        // More conservative position sizing for mean reversion
        // Risk 1.5% of account per trade (vs 2% for trend following)
        $riskPercentage = 0.015;
        $riskAmount = $accountBalance * $riskPercentage;

        $latestCandle = end($marketData->ohlcvData[$marketData->timeframes[0]]);
        $currentPrice = $latestCandle['close'];

        // Calculate stop loss distance based on Bollinger Band width
        $closePrices = array_column($marketData->ohlcvData[$marketData->timeframes[0]], 'close');
        $bollingerBands = $this->indicatorService->calculateBollingerBands($closePrices, $this->bollingerPeriod, $this->bollingerStdDev);

        $bandWidth = $bollingerBands['upper'] - $bollingerBands['lower'];
        $stopLossDistance = $bandWidth / 2; // Half band width

        if ($stopLossDistance <= 0) {
            $stopLossDistance = $currentPrice * 0.02; // Fallback 2%
        }

        $positionSize = $riskAmount / $stopLossDistance;

        return round($positionSize, 8);
    }

    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $analysis = null): float
    {
        // Stop loss beyond the outer Bollinger Band
        if ($analysis !== null) {
            if ($side === 'LONG') {
                // Stop below lower band with buffer
                $lowerBand = $analysis['bollinger_lower'];
                $buffer = ($analysis['bollinger_middle'] - $lowerBand) * 0.2;
                return round($lowerBand - $buffer, 2);
            } else {
                // Stop above upper band with buffer
                $upperBand = $analysis['bollinger_upper'];
                $buffer = ($upperBand - $analysis['bollinger_middle']) * 0.2;
                return round($upperBand + $buffer, 2);
            }
        }

        // Fallback to percentage-based
        $stopLossPercentage = 0.025; // 2.5%
        if ($side === 'LONG') {
            return round($entryPrice * (1 - $stopLossPercentage), 2);
        } else {
            return round($entryPrice * (1 + $stopLossPercentage), 2);
        }
    }

    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $analysis = null): float
    {
        // Take profit at the middle band (mean)
        if ($analysis !== null) {
            return round($analysis['bollinger_middle'], 2);
        }

        // Fallback to risk:reward ratio
        $stopLoss = $this->calculateStopLoss($entryPrice, $side, $marketData, $analysis);
        $riskDistance = abs($entryPrice - $stopLoss);
        $rewardDistance = $riskDistance * $this->riskRewardRatio;

        if ($side === 'LONG') {
            return round($entryPrice + $rewardDistance, 2);
        } else {
            return round($entryPrice - $rewardDistance, 2);
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
            'max_drawdown' => 0,
            'recovery_factor' => 0,
        ];
    }

    public function optimizeParameters(array $historicalData): array
    {
        return [
            'bollinger_period' => $this->bollingerPeriod,
            'bollinger_std_dev' => $this->bollingerStdDev,
            'rsi_period' => $this->rsiPeriod,
            'rsi_oversold' => $this->rsiOversold,
            'rsi_overbought' => $this->rsiOverbought,
            'z_score_period' => $this->zScorePeriod,
            'z_score_extreme' => $this->zScoreExtreme,
            'risk_reward_ratio' => $this->riskRewardRatio,
        ];
    }
}
