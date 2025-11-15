<?php

namespace App\Services;

/**
 * Advanced Indicator Service
 *
 * Collection of advanced technical indicators beyond basic RSI, MACD, EMA.
 * Provides sophisticated analysis tools for professional trading.
 *
 * Indicators Included:
 * - Ichimoku Cloud (Tenkan, Kijun, Senkou Span A/B, Chikou)
 * - Fibonacci Retracements and Extensions
 * - Pivot Points (Standard, Fibonacci, Camarilla, Woodie)
 * - Volume Profile (VPOC, VAH, VAL)
 * - ATR-based indicators (Chandelier Exit, Keltner Channels)
 * - Advanced oscillators (Stochastic RSI, Williams %R)
 * - Market Profile
 * - Donchian Channels
 * - Parabolic SAR
 * - Awesome Oscillator
 * - Chaikin Money Flow
 * - Accumulation/Distribution Line
 * - On-Balance Volume (OBV)
 */
class AdvancedIndicatorService
{
    /**
     * Calculate Ichimoku Cloud components
     *
     * Components:
     * - Tenkan-sen (Conversion Line): (9-period high + 9-period low) / 2
     * - Kijun-sen (Base Line): (26-period high + 26-period low) / 2
     * - Senkou Span A (Leading Span A): (Tenkan-sen + Kijun-sen) / 2, plotted 26 periods ahead
     * - Senkou Span B (Leading Span B): (52-period high + 52-period low) / 2, plotted 26 periods ahead
     * - Chikou Span (Lagging Span): Current close plotted 26 periods back
     */
    public function calculateIchimokuCloud(array $ohlcvData): array
    {
        $length = count($ohlcvData);

        if ($length < 52) {
            return [
                'tenkan' => 0,
                'kijun' => 0,
                'senkou_span_a' => 0,
                'senkou_span_b' => 0,
                'chikou' => 0,
                'signal' => 'NEUTRAL',
            ];
        }

        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $closes = array_column($ohlcvData, 'close');

        // Tenkan-sen (Conversion Line) - 9 periods
        $tenkan = $this->calculateMidpoint($highs, $lows, 9);

        // Kijun-sen (Base Line) - 26 periods
        $kijun = $this->calculateMidpoint($highs, $lows, 26);

        // Senkou Span A (Leading Span A)
        $senkouSpanA = ($tenkan + $kijun) / 2;

        // Senkou Span B (Leading Span B) - 52 periods
        $senkouSpanB = $this->calculateMidpoint($highs, $lows, 52);

        // Chikou Span (Lagging Span)
        $currentClose = end($closes);
        $chikou = $currentClose;

        // Determine signal
        $signal = $this->interpretIchimoku($currentClose, $tenkan, $kijun, $senkouSpanA, $senkouSpanB);

        return [
            'tenkan' => round($tenkan, 2),
            'kijun' => round($kijun, 2),
            'senkou_span_a' => round($senkouSpanA, 2),
            'senkou_span_b' => round($senkouSpanB, 2),
            'chikou' => round($chikou, 2),
            'cloud_color' => $senkouSpanA > $senkouSpanB ? 'BULLISH' : 'BEARISH',
            'signal' => $signal,
        ];
    }

    /**
     * Calculate Fibonacci Retracement levels
     */
    public function calculateFibonacciRetracement(array $ohlcvData, int $lookback = 50): array
    {
        if (count($ohlcvData) < $lookback) {
            return [];
        }

        $recentData = array_slice($ohlcvData, -$lookback);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        $highest = max($highs);
        $lowest = min($lows);
        $diff = $highest - $lowest;

        // Determine trend direction
        $firstClose = $recentData[0]['close'];
        $lastClose = end($recentData)['close'];
        $isUptrend = $lastClose > $firstClose;

        if ($isUptrend) {
            // In uptrend, measure from low to high
            $base = $lowest;
            $top = $highest;
        } else {
            // In downtrend, measure from high to low
            $base = $highest;
            $top = $lowest;
            $diff = -$diff;
        }

        // Calculate Fibonacci levels
        $levels = [
            '0.0' => $base,
            '0.236' => $base + ($diff * 0.236),
            '0.382' => $base + ($diff * 0.382),
            '0.500' => $base + ($diff * 0.500),
            '0.618' => $base + ($diff * 0.618),
            '0.786' => $base + ($diff * 0.786),
            '1.0' => $top,
        ];

        // Calculate extensions
        $extensions = [
            '1.272' => $base + ($diff * 1.272),
            '1.618' => $base + ($diff * 1.618),
            '2.618' => $base + ($diff * 2.618),
        ];

        return [
            'retracement_levels' => array_map(fn ($v) => round($v, 2), $levels),
            'extension_levels' => array_map(fn ($v) => round($v, 2), $extensions),
            'trend' => $isUptrend ? 'UPTREND' : 'DOWNTREND',
            'swing_high' => round($highest, 2),
            'swing_low' => round($lowest, 2),
        ];
    }

    /**
     * Calculate Pivot Points (multiple methods)
     */
    public function calculatePivotPoints(array $ohlcvData, string $method = 'standard'): array
    {
        if (empty($ohlcvData)) {
            return [];
        }

        // Use yesterday's (or last period's) high, low, close
        $lastCandle = end($ohlcvData);
        $high = $lastCandle['high'];
        $low = $lastCandle['low'];
        $close = $lastCandle['close'];

        switch ($method) {
            case 'fibonacci':
                return $this->calculateFibonacciPivots($high, $low, $close);

            case 'camarilla':
                return $this->calculateCamarillaPivots($high, $low, $close);

            case 'woodie':
                return $this->calculateWoodiePivots($high, $low, $close);

            case 'standard':
            default:
                return $this->calculateStandardPivots($high, $low, $close);
        }
    }

    /**
     * Calculate Volume Profile
     */
    public function calculateVolumeProfile(array $ohlcvData, int $priceBuckets = 20): array
    {
        if (empty($ohlcvData)) {
            return [];
        }

        // Get price range
        $highs = array_column($ohlcvData, 'high');
        $lows = array_column($ohlcvData, 'low');
        $volumes = array_column($ohlcvData, 'volume');

        $highestPrice = max($highs);
        $lowestPrice = min($lows);
        $priceRange = $highestPrice - $lowestPrice;

        if ($priceRange == 0) {
            return [];
        }

        // Create price buckets
        $bucketSize = $priceRange / $priceBuckets;
        $volumeProfile = array_fill(0, $priceBuckets, 0);

        // Distribute volume across price buckets
        foreach ($ohlcvData as $candle) {
            $midPrice = ($candle['high'] + $candle['low']) / 2;
            $bucketIndex = min((int) floor(($midPrice - $lowestPrice) / $bucketSize), $priceBuckets - 1);
            $volumeProfile[$bucketIndex] += $candle['volume'];
        }

        // Find Point of Control (POC) - price level with highest volume
        $maxVolumeIndex = array_keys($volumeProfile, max($volumeProfile))[0];
        $pocPrice = $lowestPrice + ($maxVolumeIndex * $bucketSize) + ($bucketSize / 2);

        // Calculate Value Area (70% of volume)
        $totalVolume = array_sum($volumeProfile);
        $valueAreaVolume = $totalVolume * 0.70;

        $valuAreaResult = $this->calculateValueArea($volumeProfile, $bucketSize, $lowestPrice, $valueAreaVolume);

        return [
            'poc_price' => round($pocPrice, 2),
            'value_area_high' => round($valuAreaResult['vah'], 2),
            'value_area_low' => round($valuAreaResult['val'], 2),
            'total_volume' => $totalVolume,
            'profile' => array_map(fn ($i) => [
                'price_level' => round($lowestPrice + ($i * $bucketSize), 2),
                'volume' => $volumeProfile[$i],
            ], array_keys($volumeProfile)),
        ];
    }

    /**
     * Calculate Keltner Channels (ATR-based)
     */
    public function calculateKeltnerChannels(array $ohlcvData, int $emaPeriod = 20, float $atrMultiplier = 2.0): array
    {
        if (count($ohlcvData) < $emaPeriod + 1) {
            return [];
        }

        $closes = array_column($ohlcvData, 'close');
        $ema = $this->calculateEMA($closes, $emaPeriod);
        $atr = $this->calculateATR($ohlcvData, $emaPeriod);

        $upperBand = $ema + ($atr * $atrMultiplier);
        $lowerBand = $ema - ($atr * $atrMultiplier);

        $currentPrice = end($closes);

        return [
            'middle' => round($ema, 2),
            'upper' => round($upperBand, 2),
            'lower' => round($lowerBand, 2),
            'width' => round($upperBand - $lowerBand, 2),
            'position' => $this->getPositionInChannel($currentPrice, $lowerBand, $upperBand),
        ];
    }

    /**
     * Calculate Donchian Channels
     */
    public function calculateDonchianChannels(array $ohlcvData, int $period = 20): array
    {
        if (count($ohlcvData) < $period) {
            return [];
        }

        $recentData = array_slice($ohlcvData, -$period);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');

        $upperBand = max($highs);
        $lowerBand = min($lows);
        $middleBand = ($upperBand + $lowerBand) / 2;

        $currentPrice = end(array_column($ohlcvData, 'close'));

        return [
            'upper' => round($upperBand, 2),
            'middle' => round($middleBand, 2),
            'lower' => round($lowerBand, 2),
            'width' => round($upperBand - $lowerBand, 2),
            'position' => $this->getPositionInChannel($currentPrice, $lowerBand, $upperBand),
        ];
    }

    /**
     * Calculate Parabolic SAR
     */
    public function calculateParabolicSAR(array $ohlcvData, float $accelerationFactor = 0.02, float $maxAF = 0.2): array
    {
        $length = count($ohlcvData);
        if ($length < 5) {
            return [];
        }

        // Initialize
        $sar = [];
        $af = $accelerationFactor;
        $isUptrend = $ohlcvData[1]['close'] > $ohlcvData[0]['close'];
        $ep = $isUptrend ? max($ohlcvData[0]['high'], $ohlcvData[1]['high']) : min($ohlcvData[0]['low'], $ohlcvData[1]['low']);
        $currentSAR = $isUptrend ? min($ohlcvData[0]['low'], $ohlcvData[1]['low']) : max($ohlcvData[0]['high'], $ohlcvData[1]['high']);

        for ($i = 2; $i < $length; $i++) {
            $sar[] = $currentSAR;

            // Calculate new SAR
            $currentSAR = $currentSAR + $af * ($ep - $currentSAR);

            $currentHigh = $ohlcvData[$i]['high'];
            $currentLow = $ohlcvData[$i]['low'];

            if ($isUptrend) {
                // In uptrend
                if ($currentLow < $currentSAR) {
                    // Trend reversal
                    $isUptrend = false;
                    $currentSAR = $ep;
                    $ep = $currentLow;
                    $af = $accelerationFactor;
                } else {
                    // Continue uptrend
                    if ($currentHigh > $ep) {
                        $ep = $currentHigh;
                        $af = min($af + $accelerationFactor, $maxAF);
                    }
                }
            } else {
                // In downtrend
                if ($currentHigh > $currentSAR) {
                    // Trend reversal
                    $isUptrend = true;
                    $currentSAR = $ep;
                    $ep = $currentHigh;
                    $af = $accelerationFactor;
                } else {
                    // Continue downtrend
                    if ($currentLow < $ep) {
                        $ep = $currentLow;
                        $af = min($af + $accelerationFactor, $maxAF);
                    }
                }
            }
        }

        $currentPrice = end(array_column($ohlcvData, 'close'));

        return [
            'current_sar' => round($currentSAR, 2),
            'trend' => $isUptrend ? 'BULLISH' : 'BEARISH',
            'signal' => $this->getSARSignal($currentPrice, $currentSAR, $isUptrend),
        ];
    }

    /**
     * Calculate Stochastic RSI
     */
    public function calculateStochasticRSI(array $closes, int $rsiPeriod = 14, int $stochPeriod = 14): array
    {
        if (count($closes) < $rsiPeriod + $stochPeriod) {
            return [];
        }

        // Calculate RSI values
        $rsiValues = $this->calculateRSISeries($closes, $rsiPeriod);

        // Apply Stochastic to RSI
        $recentRSI = array_slice($rsiValues, -$stochPeriod);
        $maxRSI = max($recentRSI);
        $minRSI = min($recentRSI);
        $currentRSI = end($rsiValues);

        $stochRSI = 0;
        if ($maxRSI - $minRSI != 0) {
            $stochRSI = (($currentRSI - $minRSI) / ($maxRSI - $minRSI)) * 100;
        }

        return [
            'stoch_rsi' => round($stochRSI, 2),
            'signal' => $this->getStochRSISignal($stochRSI),
        ];
    }

    /**
     * Calculate Williams %R
     */
    public function calculateWilliamsR(array $ohlcvData, int $period = 14): array
    {
        if (count($ohlcvData) < $period) {
            return [];
        }

        $recentData = array_slice($ohlcvData, -$period);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');
        $currentClose = end(array_column($ohlcvData, 'close'));

        $highestHigh = max($highs);
        $lowestLow = min($lows);

        $williamsR = 0;
        if ($highestHigh - $lowestLow != 0) {
            $williamsR = (($highestHigh - $currentClose) / ($highestHigh - $lowestLow)) * -100;
        }

        return [
            'williams_r' => round($williamsR, 2),
            'signal' => $this->getWilliamsRSignal($williamsR),
        ];
    }

    /**
     * Calculate Awesome Oscillator
     */
    public function calculateAwesomeOscillator(array $ohlcvData): array
    {
        if (count($ohlcvData) < 34) {
            return [];
        }

        // Calculate median prices
        $medianPrices = array_map(fn ($candle) => ($candle['high'] + $candle['low']) / 2, $ohlcvData);

        // Calculate 5-period and 34-period SMAs of median price
        $sma5 = $this->calculateSMA($medianPrices, 5);
        $sma34 = $this->calculateSMA($medianPrices, 34);

        $ao = $sma5 - $sma34;

        return [
            'awesome_oscillator' => round($ao, 4),
            'signal' => $ao > 0 ? 'BULLISH' : 'BEARISH',
        ];
    }

    /**
     * Calculate Chaikin Money Flow
     */
    public function calculateChaikinMoneyFlow(array $ohlcvData, int $period = 20): array
    {
        if (count($ohlcvData) < $period) {
            return [];
        }

        $recentData = array_slice($ohlcvData, -$period);
        $moneyFlowVolume = 0;
        $totalVolume = 0;

        foreach ($recentData as $candle) {
            $high = $candle['high'];
            $low = $candle['low'];
            $close = $candle['close'];
            $volume = $candle['volume'];

            $range = $high - $low;
            if ($range != 0) {
                $moneyFlowMultiplier = (($close - $low) - ($high - $close)) / $range;
                $moneyFlowVolume += $moneyFlowMultiplier * $volume;
            }

            $totalVolume += $volume;
        }

        $cmf = $totalVolume != 0 ? $moneyFlowVolume / $totalVolume : 0;

        return [
            'cmf' => round($cmf, 4),
            'signal' => $this->getCMFSignal($cmf),
        ];
    }

    /**
     * Calculate Accumulation/Distribution Line
     */
    public function calculateAccumulationDistribution(array $ohlcvData): array
    {
        if (empty($ohlcvData)) {
            return [];
        }

        $adLine = 0;
        $adValues = [];

        foreach ($ohlcvData as $candle) {
            $high = $candle['high'];
            $low = $candle['low'];
            $close = $candle['close'];
            $volume = $candle['volume'];

            $range = $high - $low;
            if ($range != 0) {
                $moneyFlowMultiplier = (($close - $low) - ($high - $close)) / $range;
                $moneyFlowVolume = $moneyFlowMultiplier * $volume;
                $adLine += $moneyFlowVolume;
            }

            $adValues[] = $adLine;
        }

        $currentAD = end($adValues);
        $previousAD = count($adValues) > 1 ? $adValues[count($adValues) - 2] : $currentAD;

        return [
            'ad_line' => round($currentAD, 2),
            'trend' => $currentAD > $previousAD ? 'ACCUMULATION' : 'DISTRIBUTION',
        ];
    }

    /**
     * Calculate On-Balance Volume (OBV)
     */
    public function calculateOBV(array $ohlcvData): array
    {
        if (count($ohlcvData) < 2) {
            return [];
        }

        $obv = 0;
        $obvValues = [];

        for ($i = 0; $i < count($ohlcvData); $i++) {
            if ($i == 0) {
                $obv = $ohlcvData[$i]['volume'];
            } else {
                if ($ohlcvData[$i]['close'] > $ohlcvData[$i - 1]['close']) {
                    $obv += $ohlcvData[$i]['volume'];
                } elseif ($ohlcvData[$i]['close'] < $ohlcvData[$i - 1]['close']) {
                    $obv -= $ohlcvData[$i]['volume'];
                }
                // If close is same, OBV doesn't change
            }

            $obvValues[] = $obv;
        }

        $currentOBV = end($obvValues);
        $previousOBV = count($obvValues) > 1 ? $obvValues[count($obvValues) - 2] : $currentOBV;

        return [
            'obv' => round($currentOBV, 2),
            'trend' => $currentOBV > $previousOBV ? 'BULLISH' : 'BEARISH',
        ];
    }

    // Private helper methods

    private function calculateMidpoint(array $highs, array $lows, int $period): float
    {
        $recentHighs = array_slice($highs, -$period);
        $recentLows = array_slice($lows, -$period);

        $highest = max($recentHighs);
        $lowest = min($recentLows);

        return ($highest + $lowest) / 2;
    }

    private function interpretIchimoku(float $price, float $tenkan, float $kijun, float $spanA, float $spanB): string
    {
        $signal = 'NEUTRAL';

        // Strong bullish: price above cloud, tenkan above kijun
        if ($price > max($spanA, $spanB) && $tenkan > $kijun) {
            $signal = 'STRONG_BULLISH';
        }
        // Bullish: price above cloud
        elseif ($price > max($spanA, $spanB)) {
            $signal = 'BULLISH';
        }
        // Strong bearish: price below cloud, tenkan below kijun
        elseif ($price < min($spanA, $spanB) && $tenkan < $kijun) {
            $signal = 'STRONG_BEARISH';
        }
        // Bearish: price below cloud
        elseif ($price < min($spanA, $spanB)) {
            $signal = 'BEARISH';
        }
        // In cloud: neutral/consolidation
        else {
            $signal = 'NEUTRAL';
        }

        return $signal;
    }

    private function calculateStandardPivots(float $high, float $low, float $close): array
    {
        $pivot = ($high + $low + $close) / 3;

        $r1 = (2 * $pivot) - $low;
        $s1 = (2 * $pivot) - $high;
        $r2 = $pivot + ($high - $low);
        $s2 = $pivot - ($high - $low);
        $r3 = $high + 2 * ($pivot - $low);
        $s3 = $low - 2 * ($high - $pivot);

        return [
            'pivot' => round($pivot, 2),
            'r1' => round($r1, 2),
            'r2' => round($r2, 2),
            'r3' => round($r3, 2),
            's1' => round($s1, 2),
            's2' => round($s2, 2),
            's3' => round($s3, 2),
        ];
    }

    private function calculateFibonacciPivots(float $high, float $low, float $close): array
    {
        $pivot = ($high + $low + $close) / 3;
        $range = $high - $low;

        $r1 = $pivot + ($range * 0.382);
        $r2 = $pivot + ($range * 0.618);
        $r3 = $pivot + ($range * 1.000);
        $s1 = $pivot - ($range * 0.382);
        $s2 = $pivot - ($range * 0.618);
        $s3 = $pivot - ($range * 1.000);

        return [
            'pivot' => round($pivot, 2),
            'r1' => round($r1, 2),
            'r2' => round($r2, 2),
            'r3' => round($r3, 2),
            's1' => round($s1, 2),
            's2' => round($s2, 2),
            's3' => round($s3, 2),
        ];
    }

    private function calculateCamarillaPivots(float $high, float $low, float $close): array
    {
        $range = $high - $low;

        $r1 = $close + ($range * 1.1 / 12);
        $r2 = $close + ($range * 1.1 / 6);
        $r3 = $close + ($range * 1.1 / 4);
        $r4 = $close + ($range * 1.1 / 2);

        $s1 = $close - ($range * 1.1 / 12);
        $s2 = $close - ($range * 1.1 / 6);
        $s3 = $close - ($range * 1.1 / 4);
        $s4 = $close - ($range * 1.1 / 2);

        return [
            'r1' => round($r1, 2),
            'r2' => round($r2, 2),
            'r3' => round($r3, 2),
            'r4' => round($r4, 2),
            's1' => round($s1, 2),
            's2' => round($s2, 2),
            's3' => round($s3, 2),
            's4' => round($s4, 2),
        ];
    }

    private function calculateWoodiePivots(float $high, float $low, float $close): array
    {
        $pivot = ($high + $low + (2 * $close)) / 4;

        $r1 = (2 * $pivot) - $low;
        $s1 = (2 * $pivot) - $high;
        $r2 = $pivot + ($high - $low);
        $s2 = $pivot - ($high - $low);

        return [
            'pivot' => round($pivot, 2),
            'r1' => round($r1, 2),
            'r2' => round($r2, 2),
            's1' => round($s1, 2),
            's2' => round($s2, 2),
        ];
    }

    private function calculateValueArea(array $volumeProfile, float $bucketSize, float $lowestPrice, float $targetVolume): array
    {
        // Find POC index
        $pocIndex = array_keys($volumeProfile, max($volumeProfile))[0];

        // Expand from POC until we have 70% of volume
        $currentVolume = $volumeProfile[$pocIndex];
        $lowerIndex = $pocIndex;
        $upperIndex = $pocIndex;

        while ($currentVolume < $targetVolume && ($lowerIndex > 0 || $upperIndex < count($volumeProfile) - 1)) {
            $lowerVolume = $lowerIndex > 0 ? $volumeProfile[$lowerIndex - 1] : 0;
            $upperVolume = $upperIndex < count($volumeProfile) - 1 ? $volumeProfile[$upperIndex + 1] : 0;

            if ($lowerVolume > $upperVolume && $lowerIndex > 0) {
                $lowerIndex--;
                $currentVolume += $lowerVolume;
            } elseif ($upperIndex < count($volumeProfile) - 1) {
                $upperIndex++;
                $currentVolume += $upperVolume;
            } else {
                break;
            }
        }

        $val = $lowestPrice + ($lowerIndex * $bucketSize);
        $vah = $lowestPrice + (($upperIndex + 1) * $bucketSize);

        return [
            'val' => $val,
            'vah' => $vah,
        ];
    }

    private function calculateEMA(array $values, int $period): float
    {
        if (count($values) < $period) {
            return 0;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($values, 0, $period)) / $period;

        for ($i = $period; $i < count($values); $i++) {
            $ema = (($values[$i] - $ema) * $multiplier) + $ema;
        }

        return $ema;
    }

    private function calculateSMA(array $values, int $period): float
    {
        if (count($values) < $period) {
            return 0;
        }

        $recentValues = array_slice($values, -$period);

        return array_sum($recentValues) / count($recentValues);
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

    private function calculateRSISeries(array $closes, int $period): array
    {
        $rsiValues = [];

        for ($i = $period; $i < count($closes); $i++) {
            $segment = array_slice($closes, $i - $period, $period + 1);

            $gains = [];
            $losses = [];

            for ($j = 1; $j < count($segment); $j++) {
                $change = $segment[$j] - $segment[$j - 1];
                if ($change > 0) {
                    $gains[] = $change;
                    $losses[] = 0;
                } else {
                    $gains[] = 0;
                    $losses[] = abs($change);
                }
            }

            $avgGain = array_sum($gains) / $period;
            $avgLoss = array_sum($losses) / $period;

            if ($avgLoss == 0) {
                $rsiValues[] = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsiValues[] = 100 - (100 / (1 + $rs));
            }
        }

        return $rsiValues;
    }

    private function getPositionInChannel(float $price, float $lower, float $upper): string
    {
        $range = $upper - $lower;
        if ($range == 0) {
            return 'MIDDLE';
        }

        $position = ($price - $lower) / $range;

        if ($position >= 0.8) {
            return 'UPPER';
        }
        if ($position >= 0.6) {
            return 'UPPER_MIDDLE';
        }
        if ($position >= 0.4) {
            return 'MIDDLE';
        }
        if ($position >= 0.2) {
            return 'LOWER_MIDDLE';
        }

        return 'LOWER';
    }

    private function getSARSignal(float $price, float $sar, bool $isUptrend): string
    {
        if ($isUptrend && $price > $sar) {
            return 'BULLISH';
        } elseif (! $isUptrend && $price < $sar) {
            return 'BEARISH';
        } else {
            return 'REVERSAL';
        }
    }

    private function getStochRSISignal(float $stochRSI): string
    {
        if ($stochRSI < 20) {
            return 'OVERSOLD';
        }
        if ($stochRSI > 80) {
            return 'OVERBOUGHT';
        }

        return 'NEUTRAL';
    }

    private function getWilliamsRSignal(float $williamsR): string
    {
        if ($williamsR < -80) {
            return 'OVERSOLD';
        }
        if ($williamsR > -20) {
            return 'OVERBOUGHT';
        }

        return 'NEUTRAL';
    }

    private function getCMFSignal(float $cmf): string
    {
        if ($cmf > 0.2) {
            return 'STRONG_BUYING_PRESSURE';
        }
        if ($cmf > 0.05) {
            return 'BUYING_PRESSURE';
        }
        if ($cmf < -0.2) {
            return 'STRONG_SELLING_PRESSURE';
        }
        if ($cmf < -0.05) {
            return 'SELLING_PRESSURE';
        }

        return 'NEUTRAL';
    }
}
