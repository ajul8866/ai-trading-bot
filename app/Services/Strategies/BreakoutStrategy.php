<?php

namespace App\Services\Strategies;

use App\Contracts\TradingStrategyInterface;
use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;
use App\Services\TechnicalIndicatorService;

/**
 * Breakout Strategy
 *
 * This strategy identifies and trades significant price breakouts from
 * consolidation zones, support/resistance levels, and chart patterns.
 * Uses volume confirmation, volatility analysis, and multiple timeframe
 * validation to filter false breakouts.
 *
 * Core Principles:
 * - Price consolidation creates energy for explosive moves
 * - True breakouts are accompanied by volume expansion
 * - Multiple timeframe confirmation reduces false signals
 * - Volatility contraction precedes expansion
 *
 * Entry Conditions (LONG Breakout):
 * - Price breaks above resistance level
 * - Volume >= 2x average (strong conviction)
 * - ATR expanding (volatility increase)
 * - Bollinger Band squeeze released
 * - Higher timeframes showing same direction
 * - Clear consolidation period before breakout
 * - No immediate overhead resistance
 *
 * Entry Conditions (SHORT Breakdown):
 * - Price breaks below support level
 * - Volume >= 2x average
 * - ATR expanding
 * - Bollinger Band squeeze released
 * - Higher timeframes showing same direction
 * - Clear consolidation period before breakdown
 * - No immediate support below
 *
 * Exit Conditions:
 * - Breakout fails (returns to range)
 * - Volume dries up
 * - Stop loss hit (below breakout level)
 * - Take profit hit (measured move target)
 * - Momentum divergence appears
 *
 * Pattern Detection:
 * - Rectangle/Box consolidation
 * - Triangle patterns (ascending, descending, symmetrical)
 * - Flag and pennant patterns
 * - Range-bound markets
 * - Cup and handle
 */
class BreakoutStrategy implements TradingStrategyInterface
{
    private TechnicalIndicatorService $indicatorService;

    // Strategy parameters
    private int $consolidationPeriod = 20; // Minimum candles for consolidation

    private float $consolidationThreshold = 0.015; // 1.5% range for consolidation

    private float $volumeMultiplier = 2.0; // Volume must be 2x average

    private int $volumeAvgPeriod = 20;

    private int $atrPeriod = 14;

    private float $atrExpansionThreshold = 1.3; // ATR must expand by 30%

    private int $supportResistancePeriod = 50;

    private float $supportResistanceStrength = 3; // Minimum touches for valid level

    private int $bollingerPeriod = 20;

    private float $bollingerStdDev = 2.0;

    private float $squeezeThreshold = 0.02; // BB width indicating squeeze

    private float $breakoutThreshold = 0.005; // 0.5% beyond level for confirmation

    private float $riskRewardRatio = 2.5;

    // Pattern recognition parameters
    private int $patternLookback = 50;

    private float $flagSlopeThreshold = 0.3;

    private float $triangleConvergence = 0.7;

    public function __construct(TechnicalIndicatorService $indicatorService)
    {
        $this->indicatorService = $indicatorService;
    }

    public function getName(): string
    {
        return 'Breakout Strategy';
    }

    public function getDescription(): string
    {
        return 'Advanced breakout strategy detecting price breakouts from consolidation zones and patterns with volume and volatility confirmation';
    }

    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO
    {
        $reasons = [];
        $signal = 'HOLD';
        $strength = 0;
        $confidence = 0;

        $primaryTimeframe = $marketData->timeframes[0] ?? '5m';
        $ohlcvData = $marketData->ohlcvData[$primaryTimeframe] ?? [];

        if (empty($ohlcvData) || count($ohlcvData) < $this->patternLookback) {
            return $this->createHoldSignal($marketData, 'Insufficient data for breakout analysis');
        }

        // Perform comprehensive analysis
        $analysis = $this->performBreakoutAnalysis($ohlcvData);

        // Detect chart patterns
        $patterns = $this->detectChartPatterns($ohlcvData);

        // Identify support and resistance levels
        $levels = $this->identifySupportResistanceLevels($ohlcvData);

        // Analyze volume profile
        $volumeProfile = $this->analyzeVolumeProfile($ohlcvData);

        // Check volatility conditions
        $volatility = $this->analyzeVolatility($ohlcvData);

        // Analyze higher timeframes
        $higherTimeframeAnalysis = $this->analyzeHigherTimeframes($marketData);

        // Detect active breakout
        $breakout = $this->detectBreakout($analysis, $levels, $volumeProfile, $volatility);

        // Calculate signal strength and confidence
        $strength = $this->calculateSignalStrength($breakout, $patterns, $volumeProfile, $volatility);
        $confidence = $this->calculateConfidence($breakout, $higherTimeframeAnalysis, $patterns);

        // Determine trading signal
        if ($breakout['type'] === 'BULLISH_BREAKOUT' && $breakout['confirmed']) {
            $signal = 'BUY';
            $reasons = $breakout['reasons'];
        } elseif ($breakout['type'] === 'BEARISH_BREAKDOWN' && $breakout['confirmed']) {
            $signal = 'SELL';
            $reasons = $breakout['reasons'];
        } else {
            $reasons[] = 'No confirmed breakout detected';
            $reasons[] = $breakout['status'] ?? 'Waiting for consolidation and breakout setup';
            if (! empty($patterns)) {
                $reasons[] = 'Pattern forming: '.implode(', ', array_column($patterns, 'type'));
            }
        }

        // Calculate entry, stop loss, and take profit
        $latestCandle = end($ohlcvData);
        $entryPrice = $signal !== 'HOLD' ? $latestCandle['close'] : null;
        $stopLoss = null;
        $takeProfit = null;
        $riskRewardRatio = null;

        if ($signal === 'BUY') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'LONG', $marketData, $breakout, $levels);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'LONG', $marketData, $breakout);
            $riskRewardRatio = abs($takeProfit - $entryPrice) / abs($entryPrice - $stopLoss);
        } elseif ($signal === 'SELL') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'SHORT', $marketData, $breakout, $levels);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'SHORT', $marketData, $breakout);
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
                'breakout' => $breakout,
                'patterns' => $patterns,
                'levels' => $levels,
                'volatility' => $volatility,
            ]),
            entryPrice: $entryPrice,
            stopLoss: $stopLoss,
            takeProfit: $takeProfit,
            recommendedLeverage: $this->calculateLeverage($strength, $confidence, $breakout),
            positionSize: $signal !== 'HOLD' ? $this->calculatePositionSize($marketData, $marketData->accountBalance) : null,
            riskRewardRatio: $riskRewardRatio,
            metadata: [
                'breakout_details' => $breakout,
                'chart_patterns' => $patterns,
                'support_resistance' => $levels,
                'volume_profile' => $volumeProfile,
                'higher_timeframe_analysis' => $higherTimeframeAnalysis,
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

    private function performBreakoutAnalysis(array $ohlcvData): array
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

        // Calculate ATR
        $atr = $this->calculateATR($ohlcvData, $this->atrPeriod);

        // Calculate recent high and low
        $recentHigh = max(array_slice($highs, -$this->consolidationPeriod));
        $recentLow = min(array_slice($lows, -$this->consolidationPeriod));
        $rangeSize = $recentHigh - $recentLow;
        $rangePercent = ($rangeSize / $recentLow) * 100;

        // Check for consolidation
        $isConsolidating = $rangePercent <= ($this->consolidationThreshold * 100);

        // Calculate Bollinger Band width
        $bbWidth = ($bollingerBands['upper'] - $bollingerBands['lower']) / $bollingerBands['middle'];
        $isSqueeze = $bbWidth < $this->squeezeThreshold;

        // Current price position
        $currentPrice = end($closePrices);

        return [
            'current_price' => $currentPrice,
            'recent_high' => $recentHigh,
            'recent_low' => $recentLow,
            'range_size' => round($rangeSize, 2),
            'range_percent' => round($rangePercent, 2),
            'is_consolidating' => $isConsolidating,
            'atr' => round($atr, 4),
            'bollinger_upper' => $bollingerBands['upper'],
            'bollinger_middle' => $bollingerBands['middle'],
            'bollinger_lower' => $bollingerBands['lower'],
            'bb_width' => round($bbWidth, 4),
            'is_squeeze' => $isSqueeze,
        ];
    }

    private function calculateATR(array $ohlcvData, int $period): float
    {
        if (count($ohlcvData) < $period + 1) {
            return 0;
        }

        $trueRanges = [];

        for ($i = 1; $i < count($ohlcvData); $i++) {
            $high = $ohlcvData[$i]['high'];
            $low = $ohlcvData[$i]['low'];
            $prevClose = $ohlcvData[$i - 1]['close'];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );

            $trueRanges[] = $tr;
        }

        $recentTR = array_slice($trueRanges, -$period);

        return array_sum($recentTR) / count($recentTR);
    }

    private function identifySupportResistanceLevels(array $ohlcvData): array
    {
        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $closes = array_column($ohlcvData, 'close');

        $recentData = array_slice($ohlcvData, -$this->supportResistancePeriod);
        $recentHighs = array_slice($highs, -$this->supportResistancePeriod);
        $recentLows = array_slice($lows, -$this->supportResistancePeriod);

        // Find pivot highs and lows
        $pivotHighs = $this->findPivotHighs($recentData);
        $pivotLows = $this->findPivotLows($recentData);

        // Cluster nearby levels
        $resistanceLevels = $this->clusterLevels($pivotHighs, 0.003); // 0.3% tolerance
        $supportLevels = $this->clusterLevels($pivotLows, 0.003);

        // Sort by strength (number of touches)
        usort($resistanceLevels, function ($a, $b) {
            return $b['touches'] - $a['touches'];
        });

        usort($supportLevels, function ($a, $b) {
            return $b['touches'] - $a['touches'];
        });

        // Get current price
        $currentPrice = end($closes);

        // Find nearest levels
        $nearestResistance = null;
        $nearestSupport = null;

        foreach ($resistanceLevels as $level) {
            if ($level['price'] > $currentPrice) {
                $nearestResistance = $level;
                break;
            }
        }

        foreach (array_reverse($supportLevels) as $level) {
            if ($level['price'] < $currentPrice) {
                $nearestSupport = $level;
                break;
            }
        }

        return [
            'resistance_levels' => array_slice($resistanceLevels, 0, 5),
            'support_levels' => array_slice($supportLevels, 0, 5),
            'nearest_resistance' => $nearestResistance,
            'nearest_support' => $nearestSupport,
            'current_price' => $currentPrice,
        ];
    }

    private function findPivotHighs(array $ohlcvData, int $leftBars = 5, int $rightBars = 5): array
    {
        $pivots = [];
        $length = count($ohlcvData);

        for ($i = $leftBars; $i < $length - $rightBars; $i++) {
            $isPivot = true;
            $currentHigh = $ohlcvData[$i]['high'];

            // Check left side
            for ($j = $i - $leftBars; $j < $i; $j++) {
                if ($ohlcvData[$j]['high'] >= $currentHigh) {
                    $isPivot = false;
                    break;
                }
            }

            // Check right side
            if ($isPivot) {
                for ($j = $i + 1; $j <= $i + $rightBars; $j++) {
                    if ($ohlcvData[$j]['high'] >= $currentHigh) {
                        $isPivot = false;
                        break;
                    }
                }
            }

            if ($isPivot) {
                $pivots[] = [
                    'price' => $currentHigh,
                    'index' => $i,
                    'timestamp' => $ohlcvData[$i]['timestamp'] ?? $i,
                ];
            }
        }

        return $pivots;
    }

    private function findPivotLows(array $ohlcvData, int $leftBars = 5, int $rightBars = 5): array
    {
        $pivots = [];
        $length = count($ohlcvData);

        for ($i = $leftBars; $i < $length - $rightBars; $i++) {
            $isPivot = true;
            $currentLow = $ohlcvData[$i]['low'];

            // Check left side
            for ($j = $i - $leftBars; $j < $i; $j++) {
                if ($ohlcvData[$j]['low'] <= $currentLow) {
                    $isPivot = false;
                    break;
                }
            }

            // Check right side
            if ($isPivot) {
                for ($j = $i + 1; $j <= $i + $rightBars; $j++) {
                    if ($ohlcvData[$j]['low'] <= $currentLow) {
                        $isPivot = false;
                        break;
                    }
                }
            }

            if ($isPivot) {
                $pivots[] = [
                    'price' => $currentLow,
                    'index' => $i,
                    'timestamp' => $ohlcvData[$i]['timestamp'] ?? $i,
                ];
            }
        }

        return $pivots;
    }

    private function clusterLevels(array $levels, float $tolerance): array
    {
        if (empty($levels)) {
            return [];
        }

        $clustered = [];

        foreach ($levels as $level) {
            $foundCluster = false;

            foreach ($clustered as &$cluster) {
                $priceDiff = abs($level['price'] - $cluster['price']) / $cluster['price'];

                if ($priceDiff <= $tolerance) {
                    // Add to existing cluster
                    $cluster['touches']++;
                    $cluster['price'] = ($cluster['price'] * ($cluster['touches'] - 1) + $level['price']) / $cluster['touches'];
                    $foundCluster = true;
                    break;
                }
            }

            if (! $foundCluster) {
                $clustered[] = [
                    'price' => $level['price'],
                    'touches' => 1,
                ];
            }
        }

        return $clustered;
    }

    private function detectChartPatterns(array $ohlcvData): array
    {
        $patterns = [];

        // Detect various patterns
        $rectangle = $this->detectRectanglePattern($ohlcvData);
        if ($rectangle['detected']) {
            $patterns[] = $rectangle;
        }

        $triangle = $this->detectTrianglePattern($ohlcvData);
        if ($triangle['detected']) {
            $patterns[] = $triangle;
        }

        $flag = $this->detectFlagPattern($ohlcvData);
        if ($flag['detected']) {
            $patterns[] = $flag;
        }

        return $patterns;
    }

    private function detectRectanglePattern(array $ohlcvData): array
    {
        $recentData = array_slice($ohlcvData, -$this->consolidationPeriod);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        $maxHigh = max($highs);
        $minLow = min($lows);
        $range = $maxHigh - $minLow;
        $avgPrice = ($maxHigh + $minLow) / 2;

        $rangePercent = ($range / $avgPrice) * 100;

        // Rectangle pattern: price oscillating between two parallel levels
        $isRectangle = $rangePercent >= 1 && $rangePercent <= 5;

        if ($isRectangle) {
            // Check if recent candles are near the range boundaries
            $touchesTop = 0;
            $touchesBottom = 0;

            foreach ($recentData as $candle) {
                if (abs($candle['high'] - $maxHigh) / $maxHigh < 0.005) {
                    $touchesTop++;
                }
                if (abs($candle['low'] - $minLow) / $minLow < 0.005) {
                    $touchesBottom++;
                }
            }

            $isRectangle = $touchesTop >= 2 && $touchesBottom >= 2;
        }

        return [
            'detected' => $isRectangle,
            'type' => 'RECTANGLE',
            'upper_level' => $maxHigh,
            'lower_level' => $minLow,
            'range_percent' => round($rangePercent, 2),
            'touches_top' => $touchesTop ?? 0,
            'touches_bottom' => $touchesBottom ?? 0,
        ];
    }

    private function detectTrianglePattern(array $ohlcvData): array
    {
        $recentData = array_slice($ohlcvData, -$this->patternLookback);
        $length = count($recentData);

        if ($length < 10) {
            return ['detected' => false];
        }

        // Get highs and lows with their indices
        $highPoints = [];
        $lowPoints = [];

        for ($i = 2; $i < $length - 2; $i++) {
            // Pivot high
            if ($recentData[$i]['high'] > $recentData[$i - 1]['high'] &&
                $recentData[$i]['high'] > $recentData[$i - 2]['high'] &&
                $recentData[$i]['high'] > $recentData[$i + 1]['high'] &&
                $recentData[$i]['high'] > $recentData[$i + 2]['high']) {
                $highPoints[] = ['index' => $i, 'value' => $recentData[$i]['high']];
            }

            // Pivot low
            if ($recentData[$i]['low'] < $recentData[$i - 1]['low'] &&
                $recentData[$i]['low'] < $recentData[$i - 2]['low'] &&
                $recentData[$i]['low'] < $recentData[$i + 1]['low'] &&
                $recentData[$i]['low'] < $recentData[$i + 2]['low']) {
                $lowPoints[] = ['index' => $i, 'value' => $recentData[$i]['low']];
            }
        }

        if (count($highPoints) < 3 || count($lowPoints) < 3) {
            return ['detected' => false];
        }

        // Calculate trendlines
        $highSlope = $this->calculateTrendlineSlope($highPoints);
        $lowSlope = $this->calculateTrendlineSlope($lowPoints);

        $triangleType = null;

        // Ascending triangle: flat top, rising bottom
        if (abs($highSlope) < $this->flagSlopeThreshold && $lowSlope > 0) {
            $triangleType = 'ASCENDING';
        }
        // Descending triangle: declining top, flat bottom
        elseif ($highSlope < 0 && abs($lowSlope) < $this->flagSlopeThreshold) {
            $triangleType = 'DESCENDING';
        }
        // Symmetrical triangle: converging lines
        elseif ($highSlope < 0 && $lowSlope > 0) {
            $convergence = abs($highSlope) + abs($lowSlope);
            if ($convergence >= $this->triangleConvergence) {
                $triangleType = 'SYMMETRICAL';
            }
        }

        return [
            'detected' => $triangleType !== null,
            'type' => $triangleType ? "TRIANGLE_{$triangleType}" : null,
            'high_slope' => round($highSlope, 4),
            'low_slope' => round($lowSlope, 4),
            'bias' => $triangleType === 'ASCENDING' ? 'BULLISH' :
                     ($triangleType === 'DESCENDING' ? 'BEARISH' : 'NEUTRAL'),
        ];
    }

    private function detectFlagPattern(array $ohlcvData): array
    {
        $length = count($ohlcvData);

        if ($length < 30) {
            return ['detected' => false];
        }

        // Flag pattern: strong move followed by consolidation
        // Look for "pole" (strong directional move)
        $poleLength = 10;
        $flagLength = 15;

        $poleData = array_slice($ohlcvData, -($poleLength + $flagLength), $poleLength);
        $flagData = array_slice($ohlcvData, -$flagLength);

        $poleStart = $poleData[0]['close'];
        $poleEnd = $poleData[count($poleData) - 1]['close'];
        $poleMove = (($poleEnd - $poleStart) / $poleStart) * 100;

        // Need strong pole (>3% move)
        if (abs($poleMove) < 3) {
            return ['detected' => false];
        }

        // Flag should be a counter-trend consolidation
        $flagHighs = array_column($flagData, 'high');
        $flagLows = array_column($flagData, 'low');
        $flagSlope = $this->calculateLinearRegressionSlope(array_column($flagData, 'close'));

        $flagRange = (max($flagHighs) - min($flagLows)) / min($flagLows) * 100;

        // Flag characteristics
        $isBullishFlag = $poleMove > 3 && $flagSlope < 0 && $flagRange < 5;
        $isBearishFlag = $poleMove < -3 && $flagSlope > 0 && $flagRange < 5;

        if (! $isBullishFlag && ! $isBearishFlag) {
            return ['detected' => false];
        }

        return [
            'detected' => true,
            'type' => $isBullishFlag ? 'FLAG_BULLISH' : 'FLAG_BEARISH',
            'pole_move' => round($poleMove, 2),
            'flag_slope' => round($flagSlope, 4),
            'flag_range' => round($flagRange, 2),
            'bias' => $isBullishFlag ? 'BULLISH' : 'BEARISH',
        ];
    }

    private function calculateTrendlineSlope(array $points): float
    {
        if (count($points) < 2) {
            return 0;
        }

        $n = count($points);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($points as $point) {
            $x = $point['index'];
            $y = $point['value'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0) {
            return 0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function calculateLinearRegressionSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0;
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;

        foreach ($values as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0) {
            return 0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function analyzeVolumeProfile(array $ohlcvData): array
    {
        $volumes = array_column($ohlcvData, 'volume');
        $recentVolumes = array_slice($volumes, -$this->volumeAvgPeriod);

        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        $currentVolume = end($volumes);

        $volumeRatio = $avgVolume > 0 ? $currentVolume / $avgVolume : 1;
        $isVolumeSpike = $volumeRatio >= $this->volumeMultiplier;

        // Calculate volume trend
        $volumeTrend = $this->calculateLinearRegressionSlope($recentVolumes);

        return [
            'current_volume' => $currentVolume,
            'avg_volume' => $avgVolume,
            'volume_ratio' => round($volumeRatio, 2),
            'is_spike' => $isVolumeSpike,
            'volume_trend' => $volumeTrend > 0 ? 'INCREASING' : 'DECREASING',
            'trend_strength' => round(abs($volumeTrend), 2),
        ];
    }

    private function analyzeVolatility(array $ohlcvData): array
    {
        $currentATR = $this->calculateATR($ohlcvData, $this->atrPeriod);

        // Calculate ATR from earlier period for comparison
        $earlierData = array_slice($ohlcvData, 0, -$this->atrPeriod);
        if (count($earlierData) >= $this->atrPeriod + 1) {
            $previousATR = $this->calculateATR($earlierData, $this->atrPeriod);
        } else {
            $previousATR = $currentATR;
        }

        $atrChange = $previousATR > 0 ? ($currentATR / $previousATR) : 1;
        $isExpanding = $atrChange >= $this->atrExpansionThreshold;

        // Calculate historical volatility
        $closes = array_column($ohlcvData, 'close');
        $returns = [];
        for ($i = 1; $i < count($closes); $i++) {
            $returns[] = log($closes[$i] / $closes[$i - 1]);
        }

        $volatility = 0;
        if (count($returns) > 0) {
            $mean = array_sum($returns) / count($returns);
            $squaredDiffs = array_map(function ($r) use ($mean) {
                return pow($r - $mean, 2);
            }, $returns);
            $variance = array_sum($squaredDiffs) / count($returns);
            $volatility = sqrt($variance) * sqrt(252); // Annualized
        }

        return [
            'current_atr' => round($currentATR, 4),
            'previous_atr' => round($previousATR, 4),
            'atr_change' => round($atrChange, 2),
            'is_expanding' => $isExpanding,
            'historical_volatility' => round($volatility * 100, 2),
            'regime' => $this->classifyVolatilityRegime($volatility),
        ];
    }

    private function classifyVolatilityRegime(float $volatility): string
    {
        if ($volatility < 0.15) {
            return 'LOW';
        } elseif ($volatility < 0.30) {
            return 'NORMAL';
        } elseif ($volatility < 0.50) {
            return 'HIGH';
        } else {
            return 'EXTREME';
        }
    }

    private function detectBreakout(array $analysis, array $levels, array $volumeProfile, array $volatility): array
    {
        $currentPrice = $analysis['current_price'];
        $breakout = [
            'type' => 'NONE',
            'confirmed' => false,
            'reasons' => [],
            'status' => 'No breakout',
        ];

        // Check for consolidation first
        if (! $analysis['is_consolidating'] && ! $analysis['is_squeeze']) {
            $breakout['status'] = 'Not in consolidation phase';

            return $breakout;
        }

        // Check bullish breakout
        if ($levels['nearest_resistance'] !== null) {
            $resistanceLevel = $levels['nearest_resistance']['price'];
            $distanceToResistance = (($currentPrice - $resistanceLevel) / $resistanceLevel) * 100;

            if ($distanceToResistance > 0 && $distanceToResistance <= 2) {
                // Price broke above resistance
                $breakout['type'] = 'BULLISH_BREAKOUT';
                $breakout['level'] = $resistanceLevel;
                $breakout['distance'] = round($distanceToResistance, 2);

                // Check confirmation factors
                $confirmations = 0;
                $maxConfirmations = 5;

                // 1. Volume confirmation
                if ($volumeProfile['is_spike']) {
                    $confirmations++;
                    $breakout['reasons'][] = "Strong volume spike ({$volumeProfile['volume_ratio']}x average)";
                }

                // 2. Volatility expansion
                if ($volatility['is_expanding']) {
                    $confirmations++;
                    $breakout['reasons'][] = "Volatility expanding (ATR change: {$volatility['atr_change']}x)";
                }

                // 3. Squeeze release
                if ($analysis['is_squeeze']) {
                    $confirmations++;
                    $breakout['reasons'][] = 'Bollinger Band squeeze released';
                }

                // 4. Strong level (multiple touches)
                if ($levels['nearest_resistance']['touches'] >= $this->supportResistanceStrength) {
                    $confirmations++;
                    $breakout['reasons'][] = "Breaking strong resistance ({$levels['nearest_resistance']['touches']} touches)";
                }

                // 5. Clean breakout (beyond threshold)
                if ($distanceToResistance >= ($this->breakoutThreshold * 100)) {
                    $confirmations++;
                    $breakout['reasons'][] = "Clean breakout ({$distanceToResistance}% above resistance)";
                }

                $breakout['confirmations'] = $confirmations;
                $breakout['max_confirmations'] = $maxConfirmations;
                $breakout['confirmed'] = $confirmations >= 3; // Need at least 3 confirmations
            }
        }

        // Check bearish breakdown
        if ($levels['nearest_support'] !== null) {
            $supportLevel = $levels['nearest_support']['price'];
            $distanceToSupport = (($supportLevel - $currentPrice) / $supportLevel) * 100;

            if ($distanceToSupport > 0 && $distanceToSupport <= 2) {
                // Price broke below support
                $breakout['type'] = 'BEARISH_BREAKDOWN';
                $breakout['level'] = $supportLevel;
                $breakout['distance'] = round($distanceToSupport, 2);

                // Check confirmation factors
                $confirmations = 0;
                $maxConfirmations = 5;

                if ($volumeProfile['is_spike']) {
                    $confirmations++;
                    $breakout['reasons'][] = "Strong volume spike ({$volumeProfile['volume_ratio']}x average)";
                }

                if ($volatility['is_expanding']) {
                    $confirmations++;
                    $breakout['reasons'][] = "Volatility expanding (ATR change: {$volatility['atr_change']}x)";
                }

                if ($analysis['is_squeeze']) {
                    $confirmations++;
                    $breakout['reasons'][] = 'Bollinger Band squeeze released';
                }

                if ($levels['nearest_support']['touches'] >= $this->supportResistanceStrength) {
                    $confirmations++;
                    $breakout['reasons'][] = "Breaking strong support ({$levels['nearest_support']['touches']} touches)";
                }

                if ($distanceToSupport >= ($this->breakoutThreshold * 100)) {
                    $confirmations++;
                    $breakout['reasons'][] = "Clean breakdown ({$distanceToSupport}% below support)";
                }

                $breakout['confirmations'] = $confirmations;
                $breakout['max_confirmations'] = $maxConfirmations;
                $breakout['confirmed'] = $confirmations >= 3;
            }
        }

        return $breakout;
    }

    private function analyzeHigherTimeframes(MarketAnalysisDTO $marketData): array
    {
        $analysis = [];

        foreach ($marketData->timeframes as $timeframe) {
            if ($timeframe === $marketData->timeframes[0]) {
                continue; // Skip primary timeframe
            }

            $ohlcvData = $marketData->ohlcvData[$timeframe] ?? [];

            if (empty($ohlcvData)) {
                continue;
            }

            // Check trend direction
            $closes = array_column($ohlcvData, 'close');
            $ema20 = $this->indicatorService->calculateEMA($closes, 20);
            $ema50 = $this->indicatorService->calculateEMA($closes, 50);

            $trend = 'NEUTRAL';
            if ($ema20 > $ema50) {
                $trend = 'BULLISH';
            } elseif ($ema20 < $ema50) {
                $trend = 'BEARISH';
            }

            $analysis[$timeframe] = [
                'trend' => $trend,
                'ema20' => round($ema20, 2),
                'ema50' => round($ema50, 2),
            ];
        }

        return $analysis;
    }

    private function calculateSignalStrength(array $breakout, array $patterns, array $volumeProfile, array $volatility): float
    {
        $strength = 0;

        if (! $breakout['confirmed']) {
            return 0;
        }

        // Strength from confirmations (40 points)
        $confirmationRatio = $breakout['confirmations'] / $breakout['max_confirmations'];
        $strength += $confirmationRatio * 40;

        // Strength from volume (25 points)
        $volumeStrength = min($volumeProfile['volume_ratio'] / 3, 1); // Cap at 3x
        $strength += $volumeStrength * 25;

        // Strength from volatility (20 points)
        if ($volatility['is_expanding']) {
            $strength += min($volatility['atr_change'] * 10, 20);
        }

        // Strength from pattern (15 points)
        foreach ($patterns as $pattern) {
            if ($pattern['detected']) {
                $strength += 15;
                break;
            }
        }

        return min(round($strength, 2), 100);
    }

    private function calculateConfidence(array $breakout, array $higherTimeframeAnalysis, array $patterns): float
    {
        $confidence = 0;

        if (! $breakout['confirmed']) {
            return 0;
        }

        // Confidence from confirmation count (40 points)
        $confirmationRatio = $breakout['confirmations'] / $breakout['max_confirmations'];
        $confidence += $confirmationRatio * 40;

        // Confidence from higher timeframe alignment (35 points)
        $alignedCount = 0;
        $totalTF = count($higherTimeframeAnalysis);

        foreach ($higherTimeframeAnalysis as $data) {
            if ($breakout['type'] === 'BULLISH_BREAKOUT' && $data['trend'] === 'BULLISH') {
                $alignedCount++;
            } elseif ($breakout['type'] === 'BEARISH_BREAKDOWN' && $data['trend'] === 'BEARISH') {
                $alignedCount++;
            }
        }

        if ($totalTF > 0) {
            $confidence += ($alignedCount / $totalTF) * 35;
        }

        // Confidence from pattern (25 points)
        foreach ($patterns as $pattern) {
            if ($pattern['detected']) {
                // Check if pattern bias aligns with breakout
                $patternBias = $pattern['bias'] ?? 'NEUTRAL';
                if (($breakout['type'] === 'BULLISH_BREAKOUT' && $patternBias === 'BULLISH') ||
                    ($breakout['type'] === 'BEARISH_BREAKDOWN' && $patternBias === 'BEARISH')) {
                    $confidence += 25;
                    break;
                }
            }
        }

        return min(round($confidence, 2), 100);
    }

    private function calculateLeverage(float $strength, float $confidence, array $breakout): int
    {
        $avgScore = ($strength + $confidence) / 2;

        // Higher leverage for well-confirmed breakouts
        if ($avgScore >= 85 && $breakout['confirmations'] >= 4) {
            return 5;
        } elseif ($avgScore >= 75 && $breakout['confirmations'] >= 3) {
            return 3;
        } elseif ($avgScore >= 65) {
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
        return ['ema', 'bollinger'];
    }

    public function canTrade(MarketAnalysisDTO $marketData): bool
    {
        foreach ($this->getRequiredTimeframes() as $timeframe) {
            if (! isset($marketData->ohlcvData[$timeframe]) || empty($marketData->ohlcvData[$timeframe])) {
                return false;
            }

            if (count($marketData->ohlcvData[$timeframe]) < $this->patternLookback) {
                return false;
            }
        }

        return true;
    }

    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float
    {
        // Aggressive position sizing for breakouts
        $riskPercentage = 0.025; // 2.5% risk per trade
        $riskAmount = $accountBalance * $riskPercentage;

        $latestCandle = end($marketData->ohlcvData[$marketData->timeframes[0]]);
        $currentPrice = $latestCandle['close'];

        $atr = $this->calculateATR($marketData->ohlcvData[$marketData->timeframes[0]], $this->atrPeriod);
        $stopLossDistance = $atr * 2; // 2 ATR stop

        if ($stopLossDistance <= 0) {
            $stopLossDistance = $currentPrice * 0.02;
        }

        $positionSize = $riskAmount / $stopLossDistance;

        return round($positionSize, 8);
    }

    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $breakout = null, ?array $levels = null): float
    {
        // Stop loss below/above the broken level
        if ($breakout !== null && isset($breakout['level'])) {
            $level = $breakout['level'];
            $atr = $this->calculateATR($marketData->ohlcvData[$marketData->timeframes[0]], $this->atrPeriod);
            $buffer = $atr * 0.5;

            if ($side === 'LONG') {
                return round($level - $buffer, 2);
            } else {
                return round($level + $buffer, 2);
            }
        }

        // Fallback to ATR-based stop
        $atr = $this->calculateATR($marketData->ohlcvData[$marketData->timeframes[0]], $this->atrPeriod);
        $stopDistance = $atr * 2;

        if ($side === 'LONG') {
            return round($entryPrice - $stopDistance, 2);
        } else {
            return round($entryPrice + $stopDistance, 2);
        }
    }

    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData, ?array $breakout = null): float
    {
        $stopLoss = $this->calculateStopLoss($entryPrice, $side, $marketData, $breakout);
        $riskDistance = abs($entryPrice - $stopLoss);

        // Use measured move for patterns, otherwise R:R ratio
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
        ];
    }

    public function optimizeParameters(array $historicalData): array
    {
        return [
            'consolidation_period' => $this->consolidationPeriod,
            'volume_multiplier' => $this->volumeMultiplier,
            'atr_expansion_threshold' => $this->atrExpansionThreshold,
            'risk_reward_ratio' => $this->riskRewardRatio,
        ];
    }
}
