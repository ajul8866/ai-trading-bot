<?php

namespace App\Services;

/**
 * Pattern Recognition Service
 *
 * Advanced technical analysis service for detecting chart patterns in price data.
 * Identifies classic patterns used by traders to predict future price movements.
 *
 * Supported Patterns:
 *
 * Reversal Patterns:
 * - Head and Shoulders (Bullish/Bearish)
 * - Double Top / Double Bottom
 * - Triple Top / Triple Bottom
 * - Rounding Bottom / Rounding Top
 * - V-Bottom / Inverted V-Top
 *
 * Continuation Patterns:
 * - Flags (Bullish/Bearish)
 * - Pennants (Bullish/Bearish)
 * - Rectangles
 * - Triangles (Ascending, Descending, Symmetrical)
 * - Wedges (Rising, Falling)
 *
 * Candlestick Patterns:
 * - Engulfing (Bullish/Bearish)
 * - Hammer / Hanging Man
 * - Shooting Star / Inverted Hammer
 * - Doji (Various types)
 * - Morning Star / Evening Star
 * - Three White Soldiers / Three Black Crows
 * - Harami (Bullish/Bearish)
 *
 * Other Patterns:
 * - Cup and Handle
 * - Diamond Top / Bottom
 * - Island Reversal
 */
class PatternRecognitionService
{
    // Pattern detection parameters
    private float $tolerancePercent = 0.02; // 2% tolerance for level matching

    private int $minPatternLength = 10; // Minimum candles for pattern

    private int $maxPatternLength = 100; // Maximum candles for pattern

    private float $volumeConfirmationMultiplier = 1.3; // Volume should be 1.3x for confirmation

    /**
     * Detect all patterns in the provided OHLCV data
     */
    public function detectAllPatterns(array $ohlcvData): array
    {
        $patterns = [];

        // Detect reversal patterns
        $patterns = array_merge($patterns, $this->detectReversalPatterns($ohlcvData));

        // Detect continuation patterns
        $patterns = array_merge($patterns, $this->detectContinuationPatterns($ohlcvData));

        // Detect candlestick patterns
        $patterns = array_merge($patterns, $this->detectCandlestickPatterns($ohlcvData));

        // Sort by confidence
        usort($patterns, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $patterns;
    }

    /**
     * Detect reversal patterns
     */
    private function detectReversalPatterns(array $ohlcvData): array
    {
        $patterns = [];

        // Head and Shoulders
        $headAndShoulders = $this->detectHeadAndShoulders($ohlcvData);
        if ($headAndShoulders !== null) {
            $patterns[] = $headAndShoulders;
        }

        // Inverse Head and Shoulders
        $inverseHeadAndShoulders = $this->detectInverseHeadAndShoulders($ohlcvData);
        if ($inverseHeadAndShoulders !== null) {
            $patterns[] = $inverseHeadAndShoulders;
        }

        // Double Top
        $doubleTop = $this->detectDoubleTop($ohlcvData);
        if ($doubleTop !== null) {
            $patterns[] = $doubleTop;
        }

        // Double Bottom
        $doubleBottom = $this->detectDoubleBottom($ohlcvData);
        if ($doubleBottom !== null) {
            $patterns[] = $doubleBottom;
        }

        // Triple Top
        $tripleTop = $this->detectTripleTop($ohlcvData);
        if ($tripleTop !== null) {
            $patterns[] = $tripleTop;
        }

        // Triple Bottom
        $tripleBottom = $this->detectTripleBottom($ohlcvData);
        if ($tripleBottom !== null) {
            $patterns[] = $tripleBottom;
        }

        return $patterns;
    }

    /**
     * Detect Head and Shoulders pattern (bearish reversal)
     */
    private function detectHeadAndShoulders(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        // Find pivot highs
        $pivotHighs = $this->findPivotHighs($ohlcvData, 5, 5);

        if (count($pivotHighs) < 3) {
            return null;
        }

        // Look for pattern: left shoulder, head, right shoulder
        // Head should be higher than both shoulders
        // Shoulders should be roughly at the same level
        for ($i = 0; $i < count($pivotHighs) - 2; $i++) {
            $leftShoulder = $pivotHighs[$i];
            $head = $pivotHighs[$i + 1];
            $rightShoulder = $pivotHighs[$i + 2];

            // Check if head is higher than shoulders
            if ($head['value'] <= $leftShoulder['value'] || $head['value'] <= $rightShoulder['value']) {
                continue;
            }

            // Check if shoulders are at similar levels
            $shoulderDiff = abs($leftShoulder['value'] - $rightShoulder['value']) / $leftShoulder['value'];
            if ($shoulderDiff > $this->tolerancePercent * 2) {
                continue;
            }

            // Find neckline (lowest points between shoulders and head)
            $neckline = $this->findNeckline($ohlcvData, $leftShoulder['index'], $rightShoulder['index']);

            // Calculate confidence based on pattern symmetry
            $confidence = $this->calculateHeadAndShouldersConfidence(
                $leftShoulder,
                $head,
                $rightShoulder,
                $neckline
            );

            if ($confidence >= 0.6) {
                return [
                    'type' => 'HEAD_AND_SHOULDERS',
                    'direction' => 'BEARISH',
                    'confidence' => round($confidence, 2),
                    'left_shoulder' => $leftShoulder,
                    'head' => $head,
                    'right_shoulder' => $rightShoulder,
                    'neckline' => $neckline,
                    'target' => $this->calculateHeadAndShouldersTarget($head, $neckline, 'BEARISH'),
                    'completed' => $rightShoulder['index'] < ($length - 5),
                ];
            }
        }

        return null;
    }

    /**
     * Detect Inverse Head and Shoulders (bullish reversal)
     */
    private function detectInverseHeadAndShoulders(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        // Find pivot lows
        $pivotLows = $this->findPivotLows($ohlcvData, 5, 5);

        if (count($pivotLows) < 3) {
            return null;
        }

        // Look for pattern: left shoulder, head, right shoulder
        // Head should be lower than both shoulders
        for ($i = 0; $i < count($pivotLows) - 2; $i++) {
            $leftShoulder = $pivotLows[$i];
            $head = $pivotLows[$i + 1];
            $rightShoulder = $pivotLows[$i + 2];

            // Check if head is lower than shoulders
            if ($head['value'] >= $leftShoulder['value'] || $head['value'] >= $rightShoulder['value']) {
                continue;
            }

            // Check if shoulders are at similar levels
            $shoulderDiff = abs($leftShoulder['value'] - $rightShoulder['value']) / $leftShoulder['value'];
            if ($shoulderDiff > $this->tolerancePercent * 2) {
                continue;
            }

            // Find neckline (highest points between shoulders and head)
            $neckline = $this->findNecklineInverse($ohlcvData, $leftShoulder['index'], $rightShoulder['index']);

            $confidence = $this->calculateHeadAndShouldersConfidence(
                $leftShoulder,
                $head,
                $rightShoulder,
                $neckline
            );

            if ($confidence >= 0.6) {
                return [
                    'type' => 'INVERSE_HEAD_AND_SHOULDERS',
                    'direction' => 'BULLISH',
                    'confidence' => round($confidence, 2),
                    'left_shoulder' => $leftShoulder,
                    'head' => $head,
                    'right_shoulder' => $rightShoulder,
                    'neckline' => $neckline,
                    'target' => $this->calculateHeadAndShouldersTarget($head, $neckline, 'BULLISH'),
                    'completed' => $rightShoulder['index'] < ($length - 5),
                ];
            }
        }

        return null;
    }

    /**
     * Detect Double Top pattern (bearish reversal)
     */
    private function detectDoubleTop(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 20) {
            return null;
        }

        $pivotHighs = $this->findPivotHighs($ohlcvData, 5, 5);

        if (count($pivotHighs) < 2) {
            return null;
        }

        // Look for two highs at similar levels
        for ($i = 0; $i < count($pivotHighs) - 1; $i++) {
            $firstTop = $pivotHighs[$i];
            $secondTop = $pivotHighs[$i + 1];

            // Check if tops are at similar levels
            $topDiff = abs($firstTop['value'] - $secondTop['value']) / $firstTop['value'];
            if ($topDiff > $this->tolerancePercent) {
                continue;
            }

            // Find the trough (lowest point between the two tops)
            $trough = $this->findLowestBetween($ohlcvData, $firstTop['index'], $secondTop['index']);

            // Trough should be significantly lower than tops
            $depthRatio = ($firstTop['value'] - $trough['value']) / $firstTop['value'];
            if ($depthRatio < 0.03) { // At least 3% depth
                continue;
            }

            $confidence = $this->calculateDoublePatternConfidence($firstTop, $secondTop, $trough);

            if ($confidence >= 0.6) {
                return [
                    'type' => 'DOUBLE_TOP',
                    'direction' => 'BEARISH',
                    'confidence' => round($confidence, 2),
                    'first_top' => $firstTop,
                    'second_top' => $secondTop,
                    'trough' => $trough,
                    'target' => $trough['value'] - ($firstTop['value'] - $trough['value']),
                    'completed' => $secondTop['index'] < ($length - 5),
                ];
            }
        }

        return null;
    }

    /**
     * Detect Double Bottom pattern (bullish reversal)
     */
    private function detectDoubleBottom(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 20) {
            return null;
        }

        $pivotLows = $this->findPivotLows($ohlcvData, 5, 5);

        if (count($pivotLows) < 2) {
            return null;
        }

        // Look for two lows at similar levels
        for ($i = 0; $i < count($pivotLows) - 1; $i++) {
            $firstBottom = $pivotLows[$i];
            $secondBottom = $pivotLows[$i + 1];

            // Check if bottoms are at similar levels
            $bottomDiff = abs($firstBottom['value'] - $secondBottom['value']) / $firstBottom['value'];
            if ($bottomDiff > $this->tolerancePercent) {
                continue;
            }

            // Find the peak (highest point between the two bottoms)
            $peak = $this->findHighestBetween($ohlcvData, $firstBottom['index'], $secondBottom['index']);

            // Peak should be significantly higher than bottoms
            $heightRatio = ($peak['value'] - $firstBottom['value']) / $firstBottom['value'];
            if ($heightRatio < 0.03) { // At least 3% height
                continue;
            }

            $confidence = $this->calculateDoublePatternConfidence($firstBottom, $secondBottom, $peak);

            if ($confidence >= 0.6) {
                return [
                    'type' => 'DOUBLE_BOTTOM',
                    'direction' => 'BULLISH',
                    'confidence' => round($confidence, 2),
                    'first_bottom' => $firstBottom,
                    'second_bottom' => $secondBottom,
                    'peak' => $peak,
                    'target' => $peak['value'] + ($peak['value'] - $firstBottom['value']),
                    'completed' => $secondBottom['index'] < ($length - 5),
                ];
            }
        }

        return null;
    }

    /**
     * Detect Triple Top pattern
     */
    private function detectTripleTop(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        $pivotHighs = $this->findPivotHighs($ohlcvData, 5, 5);

        if (count($pivotHighs) < 3) {
            return null;
        }

        // Look for three highs at similar levels
        for ($i = 0; $i < count($pivotHighs) - 2; $i++) {
            $first = $pivotHighs[$i];
            $second = $pivotHighs[$i + 1];
            $third = $pivotHighs[$i + 2];

            // Check if all three are at similar levels
            $diff1 = abs($first['value'] - $second['value']) / $first['value'];
            $diff2 = abs($second['value'] - $third['value']) / $second['value'];
            $diff3 = abs($first['value'] - $third['value']) / $first['value'];

            if ($diff1 > $this->tolerancePercent || $diff2 > $this->tolerancePercent || $diff3 > $this->tolerancePercent) {
                continue;
            }

            // Find support level (lowest point in the pattern)
            $support = $this->findLowestBetween($ohlcvData, $first['index'], $third['index']);

            $confidence = 0.7; // Base confidence for triple patterns

            return [
                'type' => 'TRIPLE_TOP',
                'direction' => 'BEARISH',
                'confidence' => $confidence,
                'tops' => [$first, $second, $third],
                'support' => $support,
                'target' => $support['value'] - (($first['value'] + $second['value'] + $third['value']) / 3 - $support['value']),
                'completed' => $third['index'] < ($length - 5),
            ];
        }

        return null;
    }

    /**
     * Detect Triple Bottom pattern
     */
    private function detectTripleBottom(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        $pivotLows = $this->findPivotLows($ohlcvData, 5, 5);

        if (count($pivotLows) < 3) {
            return null;
        }

        // Look for three lows at similar levels
        for ($i = 0; $i < count($pivotLows) - 2; $i++) {
            $first = $pivotLows[$i];
            $second = $pivotLows[$i + 1];
            $third = $pivotLows[$i + 2];

            // Check if all three are at similar levels
            $diff1 = abs($first['value'] - $second['value']) / $first['value'];
            $diff2 = abs($second['value'] - $third['value']) / $second['value'];
            $diff3 = abs($first['value'] - $third['value']) / $first['value'];

            if ($diff1 > $this->tolerancePercent || $diff2 > $this->tolerancePercent || $diff3 > $this->tolerancePercent) {
                continue;
            }

            // Find resistance level (highest point in the pattern)
            $resistance = $this->findHighestBetween($ohlcvData, $first['index'], $third['index']);

            $confidence = 0.7;

            return [
                'type' => 'TRIPLE_BOTTOM',
                'direction' => 'BULLISH',
                'confidence' => $confidence,
                'bottoms' => [$first, $second, $third],
                'resistance' => $resistance,
                'target' => $resistance['value'] + ($resistance['value'] - ($first['value'] + $second['value'] + $third['value']) / 3),
                'completed' => $third['index'] < ($length - 5),
            ];
        }

        return null;
    }

    /**
     * Detect continuation patterns
     */
    private function detectContinuationPatterns(array $ohlcvData): array
    {
        $patterns = [];

        // Flags
        $bullishFlag = $this->detectBullishFlag($ohlcvData);
        if ($bullishFlag !== null) {
            $patterns[] = $bullishFlag;
        }

        $bearishFlag = $this->detectBearishFlag($ohlcvData);
        if ($bearishFlag !== null) {
            $patterns[] = $bearishFlag;
        }

        // Triangles
        $ascendingTriangle = $this->detectAscendingTriangle($ohlcvData);
        if ($ascendingTriangle !== null) {
            $patterns[] = $ascendingTriangle;
        }

        $descendingTriangle = $this->detectDescendingTriangle($ohlcvData);
        if ($descendingTriangle !== null) {
            $patterns[] = $descendingTriangle;
        }

        $symmetricalTriangle = $this->detectSymmetricalTriangle($ohlcvData);
        if ($symmetricalTriangle !== null) {
            $patterns[] = $symmetricalTriangle;
        }

        // Rectangle
        $rectangle = $this->detectRectangle($ohlcvData);
        if ($rectangle !== null) {
            $patterns[] = $rectangle;
        }

        return $patterns;
    }

    /**
     * Detect Bullish Flag pattern
     */
    private function detectBullishFlag(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 20) {
            return null;
        }

        // Look for strong upward move (pole) followed by slight downward consolidation (flag)
        $poleLength = 10;
        $flagLength = 10;

        if ($length < $poleLength + $flagLength) {
            return null;
        }

        $poleData = array_slice($ohlcvData, -($poleLength + $flagLength), $poleLength);
        $flagData = array_slice($ohlcvData, -$flagLength);

        // Calculate pole movement
        $poleStart = $poleData[0]['close'];
        $poleEnd = $poleData[count($poleData) - 1]['close'];
        $poleMove = (($poleEnd - $poleStart) / $poleStart) * 100;

        // Need strong upward pole (>5%)
        if ($poleMove < 5) {
            return null;
        }

        // Flag should be slight downward/sideways movement
        $flagCloses = array_column($flagData, 'close');
        $flagSlope = $this->calculateSlope($flagCloses);
        $flagStart = $flagCloses[0];
        $flagEnd = $flagCloses[count($flagCloses) - 1];
        $flagMove = (($flagEnd - $flagStart) / $flagStart) * 100;

        // Flag should retrace less than 50% of pole
        if ($flagMove < -($poleMove * 0.5)) {
            return null;
        }

        // Flag should have slight downward slope or consolidation
        if ($flagSlope > 0) {
            return null;
        }

        return [
            'type' => 'BULLISH_FLAG',
            'direction' => 'BULLISH',
            'confidence' => 0.75,
            'pole_move' => round($poleMove, 2),
            'flag_slope' => round($flagSlope, 4),
            'target' => $poleEnd + ($poleEnd - $poleStart),
            'completed' => true,
        ];
    }

    /**
     * Detect Bearish Flag pattern
     */
    private function detectBearishFlag(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 20) {
            return null;
        }

        $poleLength = 10;
        $flagLength = 10;

        if ($length < $poleLength + $flagLength) {
            return null;
        }

        $poleData = array_slice($ohlcvData, -($poleLength + $flagLength), $poleLength);
        $flagData = array_slice($ohlcvData, -$flagLength);

        // Calculate pole movement
        $poleStart = $poleData[0]['close'];
        $poleEnd = $poleData[count($poleData) - 1]['close'];
        $poleMove = (($poleEnd - $poleStart) / $poleStart) * 100;

        // Need strong downward pole (<-5%)
        if ($poleMove > -5) {
            return null;
        }

        // Flag should be slight upward/sideways movement
        $flagCloses = array_column($flagData, 'close');
        $flagSlope = $this->calculateSlope($flagCloses);

        // Flag should have slight upward slope or consolidation
        if ($flagSlope < 0) {
            return null;
        }

        return [
            'type' => 'BEARISH_FLAG',
            'direction' => 'BEARISH',
            'confidence' => 0.75,
            'pole_move' => round($poleMove, 2),
            'flag_slope' => round($flagSlope, 4),
            'target' => $poleEnd - ($poleStart - $poleEnd),
            'completed' => true,
        ];
    }

    /**
     * Detect Ascending Triangle
     */
    private function detectAscendingTriangle(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        $recentData = array_slice($ohlcvData, -50);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        // Find pivot highs and lows
        $pivotHighs = $this->findPivotHighs($recentData, 3, 3);
        $pivotLows = $this->findPivotLows($recentData, 3, 3);

        if (count($pivotHighs) < 3 || count($pivotLows) < 3) {
            return null;
        }

        // Check if highs are horizontal (resistance)
        $highValues = array_column($pivotHighs, 'value');
        $highSlope = $this->calculateSlopeFromPoints($pivotHighs);

        // Check if lows are rising (support)
        $lowSlope = $this->calculateSlopeFromPoints($pivotLows);

        // Ascending triangle: flat top, rising bottom
        if (abs($highSlope) < 0.0001 && $lowSlope > 0.0005) {
            return [
                'type' => 'ASCENDING_TRIANGLE',
                'direction' => 'BULLISH',
                'confidence' => 0.7,
                'resistance_level' => array_sum($highValues) / count($highValues),
                'support_slope' => round($lowSlope, 6),
                'breakout_target' => max($highValues) + (max($highs) - min($lows)),
                'completed' => false,
            ];
        }

        return null;
    }

    /**
     * Detect Descending Triangle
     */
    private function detectDescendingTriangle(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        $recentData = array_slice($ohlcvData, -50);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        $pivotHighs = $this->findPivotHighs($recentData, 3, 3);
        $pivotLows = $this->findPivotLows($recentData, 3, 3);

        if (count($pivotHighs) < 3 || count($pivotLows) < 3) {
            return null;
        }

        $lowValues = array_column($pivotLows, 'value');
        $lowSlope = $this->calculateSlopeFromPoints($pivotLows);
        $highSlope = $this->calculateSlopeFromPoints($pivotHighs);

        // Descending triangle: flat bottom, declining top
        if (abs($lowSlope) < 0.0001 && $highSlope < -0.0005) {
            return [
                'type' => 'DESCENDING_TRIANGLE',
                'direction' => 'BEARISH',
                'confidence' => 0.7,
                'support_level' => array_sum($lowValues) / count($lowValues),
                'resistance_slope' => round($highSlope, 6),
                'breakout_target' => min($lowValues) - (max($highs) - min($lows)),
                'completed' => false,
            ];
        }

        return null;
    }

    /**
     * Detect Symmetrical Triangle
     */
    private function detectSymmetricalTriangle(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 30) {
            return null;
        }

        $recentData = array_slice($ohlcvData, -50);

        $pivotHighs = $this->findPivotHighs($recentData, 3, 3);
        $pivotLows = $this->findPivotLows($recentData, 3, 3);

        if (count($pivotHighs) < 3 || count($pivotLows) < 3) {
            return null;
        }

        $highSlope = $this->calculateSlopeFromPoints($pivotHighs);
        $lowSlope = $this->calculateSlopeFromPoints($pivotLows);

        // Symmetrical triangle: converging lines
        if ($highSlope < -0.0003 && $lowSlope > 0.0003) {
            return [
                'type' => 'SYMMETRICAL_TRIANGLE',
                'direction' => 'NEUTRAL',
                'confidence' => 0.65,
                'resistance_slope' => round($highSlope, 6),
                'support_slope' => round($lowSlope, 6),
                'apex_distance' => $this->calculateApexDistance($pivotHighs, $pivotLows),
                'completed' => false,
            ];
        }

        return null;
    }

    /**
     * Detect Rectangle pattern
     */
    private function detectRectangle(array $ohlcvData): ?array
    {
        $length = count($ohlcvData);
        if ($length < 20) {
            return null;
        }

        $recentData = array_slice($ohlcvData, -30);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        $maxHigh = max($highs);
        $minLow = min($lows);
        $range = $maxHigh - $minLow;
        $avgPrice = ($maxHigh + $minLow) / 2;

        $rangePercent = ($range / $avgPrice) * 100;

        // Rectangle should have price oscillating in a range
        if ($rangePercent < 2 || $rangePercent > 10) {
            return null;
        }

        // Count touches at top and bottom
        $topTouches = 0;
        $bottomTouches = 0;

        foreach ($recentData as $candle) {
            if (abs($candle['high'] - $maxHigh) / $maxHigh < 0.005) {
                $topTouches++;
            }
            if (abs($candle['low'] - $minLow) / $minLow < 0.005) {
                $bottomTouches++;
            }
        }

        if ($topTouches >= 2 && $bottomTouches >= 2) {
            return [
                'type' => 'RECTANGLE',
                'direction' => 'NEUTRAL',
                'confidence' => 0.7,
                'resistance' => $maxHigh,
                'support' => $minLow,
                'range_percent' => round($rangePercent, 2),
                'top_touches' => $topTouches,
                'bottom_touches' => $bottomTouches,
                'completed' => false,
            ];
        }

        return null;
    }

    /**
     * Detect candlestick patterns
     */
    private function detectCandlestickPatterns(array $ohlcvData): array
    {
        $patterns = [];
        $length = count($ohlcvData);

        if ($length < 3) {
            return $patterns;
        }

        // Get last 3 candles for pattern detection
        $current = $ohlcvData[$length - 1];
        $previous = $ohlcvData[$length - 2];
        $previous2 = $ohlcvData[$length - 3];

        // Bullish Engulfing
        if ($this->isBullishEngulfing($previous, $current)) {
            $patterns[] = [
                'type' => 'BULLISH_ENGULFING',
                'direction' => 'BULLISH',
                'confidence' => 0.75,
                'candles' => [$previous, $current],
                'position' => 'recent',
            ];
        }

        // Bearish Engulfing
        if ($this->isBearishEngulfing($previous, $current)) {
            $patterns[] = [
                'type' => 'BEARISH_ENGULFING',
                'direction' => 'BEARISH',
                'confidence' => 0.75,
                'candles' => [$previous, $current],
                'position' => 'recent',
            ];
        }

        // Hammer
        if ($this->isHammer($current)) {
            $patterns[] = [
                'type' => 'HAMMER',
                'direction' => 'BULLISH',
                'confidence' => 0.7,
                'candles' => [$current],
                'position' => 'recent',
            ];
        }

        // Shooting Star
        if ($this->isShootingStar($current)) {
            $patterns[] = [
                'type' => 'SHOOTING_STAR',
                'direction' => 'BEARISH',
                'confidence' => 0.7,
                'candles' => [$current],
                'position' => 'recent',
            ];
        }

        // Doji
        if ($this->isDoji($current)) {
            $patterns[] = [
                'type' => 'DOJI',
                'direction' => 'NEUTRAL',
                'confidence' => 0.6,
                'candles' => [$current],
                'position' => 'recent',
            ];
        }

        // Morning Star
        if ($this->isMorningStar($previous2, $previous, $current)) {
            $patterns[] = [
                'type' => 'MORNING_STAR',
                'direction' => 'BULLISH',
                'confidence' => 0.8,
                'candles' => [$previous2, $previous, $current],
                'position' => 'recent',
            ];
        }

        // Evening Star
        if ($this->isEveningStar($previous2, $previous, $current)) {
            $patterns[] = [
                'type' => 'EVENING_STAR',
                'direction' => 'BEARISH',
                'confidence' => 0.8,
                'candles' => [$previous2, $previous, $current],
                'position' => 'recent',
            ];
        }

        // Three White Soldiers
        if ($length >= 3 && $this->isThreeWhiteSoldiers($previous2, $previous, $current)) {
            $patterns[] = [
                'type' => 'THREE_WHITE_SOLDIERS',
                'direction' => 'BULLISH',
                'confidence' => 0.85,
                'candles' => [$previous2, $previous, $current],
                'position' => 'recent',
            ];
        }

        // Three Black Crows
        if ($length >= 3 && $this->isThreeBlackCrows($previous2, $previous, $current)) {
            $patterns[] = [
                'type' => 'THREE_BLACK_CROWS',
                'direction' => 'BEARISH',
                'confidence' => 0.85,
                'candles' => [$previous2, $previous, $current],
                'position' => 'recent',
            ];
        }

        return $patterns;
    }

    // Helper methods for finding pivots and levels

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
                    'value' => $currentHigh,
                    'index' => $i,
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
                    'value' => $currentLow,
                    'index' => $i,
                ];
            }
        }

        return $pivots;
    }

    private function findNeckline(array $ohlcvData, int $start, int $end): array
    {
        $segment = array_slice($ohlcvData, $start, $end - $start + 1);
        $lows = array_column($segment, 'low');
        $minLow = min($lows);
        $minIndex = array_search($minLow, $lows) + $start;

        return [
            'value' => $minLow,
            'index' => $minIndex,
        ];
    }

    private function findNecklineInverse(array $ohlcvData, int $start, int $end): array
    {
        $segment = array_slice($ohlcvData, $start, $end - $start + 1);
        $highs = array_column($segment, 'high');
        $maxHigh = max($highs);
        $maxIndex = array_search($maxHigh, $highs) + $start;

        return [
            'value' => $maxHigh,
            'index' => $maxIndex,
        ];
    }

    private function findLowestBetween(array $ohlcvData, int $start, int $end): array
    {
        $segment = array_slice($ohlcvData, $start, $end - $start + 1);
        $lows = array_column($segment, 'low');
        $minLow = min($lows);
        $minIndex = array_search($minLow, $lows) + $start;

        return [
            'value' => $minLow,
            'index' => $minIndex,
        ];
    }

    private function findHighestBetween(array $ohlcvData, int $start, int $end): array
    {
        $segment = array_slice($ohlcvData, $start, $end - $start + 1);
        $highs = array_column($segment, 'high');
        $maxHigh = max($highs);
        $maxIndex = array_search($maxHigh, $highs) + $start;

        return [
            'value' => $maxHigh,
            'index' => $maxIndex,
        ];
    }

    private function calculateHeadAndShouldersConfidence($leftShoulder, $head, $head, $neckline): float
    {
        $confidence = 0.5; // Base

        // Check symmetry of shoulders
        $shoulderDiff = abs($leftShoulder['value'] - $rightShoulder['value']) / $leftShoulder['value'];
        if ($shoulderDiff < 0.01) {
            $confidence += 0.2;
        } elseif ($shoulderDiff < 0.02) {
            $confidence += 0.1;
        }

        // Check head prominence
        $headProminence = ($head['value'] - max($leftShoulder['value'], $rightShoulder['value'])) / $head['value'];
        if ($headProminence > 0.05) {
            $confidence += 0.2;
        } elseif ($headProminence > 0.03) {
            $confidence += 0.1;
        }

        return min($confidence, 1.0);
    }

    private function calculateHeadAndShouldersTarget($head, $neckline, string $direction): float
    {
        $distance = abs($head['value'] - $neckline['value']);

        if ($direction === 'BEARISH') {
            return $neckline['value'] - $distance;
        } else {
            return $neckline['value'] + $distance;
        }
    }

    private function calculateDoublePatternConfidence($first, $second, $middle): float
    {
        $confidence = 0.6; // Base

        // Check similarity of the two peaks/troughs
        $similarity = 1 - (abs($first['value'] - $second['value']) / $first['value']);
        $confidence += $similarity * 0.3;

        // Check depth/height of middle point
        $depth = abs($first['value'] - $middle['value']) / $first['value'];
        if ($depth > 0.05) {
            $confidence += 0.1;
        }

        return min($confidence, 1.0);
    }

    private function calculateSlope(array $values): float
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

    private function calculateSlopeFromPoints(array $points): float
    {
        $n = count($points);
        if ($n < 2) {
            return 0;
        }

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

    private function calculateApexDistance(array $highs, array $lows): int
    {
        // Simplified calculation
        return 10; // Placeholder
    }

    // Candlestick pattern detectors

    private function isBullishEngulfing(array $prev, array $curr): bool
    {
        return $prev['close'] < $prev['open']
            && $curr['close'] > $curr['open']
            && $curr['open'] < $prev['close']
            && $curr['close'] > $prev['open'];
    }

    private function isBearishEngulfing(array $prev, array $curr): bool
    {
        return $prev['close'] > $prev['open']
            && $curr['close'] < $curr['open']
            && $curr['open'] > $prev['close']
            && $curr['close'] < $prev['open'];
    }

    private function isHammer(array $candle): bool
    {
        $body = abs($candle['close'] - $candle['open']);
        $lowerWick = min($candle['open'], $candle['close']) - $candle['low'];
        $upperWick = $candle['high'] - max($candle['open'], $candle['close']);

        return $lowerWick > ($body * 2) && $upperWick < ($body * 0.3);
    }

    private function isShootingStar(array $candle): bool
    {
        $body = abs($candle['close'] - $candle['open']);
        $lowerWick = min($candle['open'], $candle['close']) - $candle['low'];
        $upperWick = $candle['high'] - max($candle['open'], $candle['close']);

        return $upperWick > ($body * 2) && $lowerWick < ($body * 0.3);
    }

    private function isDoji(array $candle): bool
    {
        $body = abs($candle['close'] - $candle['open']);
        $range = $candle['high'] - $candle['low'];

        return $range > 0 && ($body / $range) < 0.1;
    }

    private function isMorningStar(array $first, array $second, array $third): bool
    {
        return $first['close'] < $first['open'] // First bearish
            && abs($second['close'] - $second['open']) < (($second['high'] - $second['low']) * 0.3) // Second small body
            && $third['close'] > $third['open'] // Third bullish
            && $third['close'] > ($first['open'] + $first['close']) / 2; // Third closes above midpoint of first
    }

    private function isEveningStar(array $first, array $second, array $third): bool
    {
        return $first['close'] > $first['open'] // First bullish
            && abs($second['close'] - $second['open']) < (($second['high'] - $second['low']) * 0.3) // Second small body
            && $third['close'] < $third['open'] // Third bearish
            && $third['close'] < ($first['open'] + $first['close']) / 2; // Third closes below midpoint of first
    }

    private function isThreeWhiteSoldiers(array $first, array $second, array $third): bool
    {
        return $first['close'] > $first['open']
            && $second['close'] > $second['open']
            && $third['close'] > $third['open']
            && $second['close'] > $first['close']
            && $third['close'] > $second['close'];
    }

    private function isThreeBlackCrows(array $first, array $second, array $third): bool
    {
        return $first['close'] < $first['open']
            && $second['close'] < $second['open']
            && $third['close'] < $third['open']
            && $second['close'] < $first['close']
            && $third['close'] < $second['close'];
    }
}
