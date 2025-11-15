<?php

namespace App\Services;

class TechnicalIndicatorService
{
    /**
     * Calculate RSI (Relative Strength Index)
     */
    public function calculateRSI(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 50.0; // Neutral RSI if not enough data
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    /**
     * Calculate MACD (Moving Average Convergence Divergence)
     */
    public function calculateMACD(array $prices, int $fastPeriod = 12, int $slowPeriod = 26, int $signalPeriod = 9): array
    {
        $emaFast = $this->calculateEMA($prices, $fastPeriod);
        $emaSlow = $this->calculateEMA($prices, $slowPeriod);
        $macd = $emaFast - $emaSlow;

        return [
            'macd' => round($macd, 2),
            'signal' => 0, // Simplified - would need historical MACD values for proper signal
            'histogram' => round($macd, 2),
        ];
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return $prices[count($prices) - 1] ?? 0;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($prices, 0, $period)) / $period;

        for ($i = $period; $i < count($prices); $i++) {
            $ema = ($prices[$i] - $ema) * $multiplier + $ema;
        }

        return round($ema, 2);
    }

    /**
     * Calculate Bollinger Bands
     */
    public function calculateBollingerBands(array $prices, int $period = 20, float $stdDev = 2): array
    {
        if (count($prices) < $period) {
            $currentPrice = $prices[count($prices) - 1] ?? 0;
            return [
                'upper' => $currentPrice * 1.02,
                'middle' => $currentPrice,
                'lower' => $currentPrice * 0.98,
            ];
        }

        $slice = array_slice($prices, -$period);
        $sma = array_sum($slice) / $period;

        // Calculate standard deviation
        $variance = 0;
        foreach ($slice as $price) {
            $variance += pow($price - $sma, 2);
        }
        $stdDeviation = sqrt($variance / $period);

        return [
            'upper' => round($sma + ($stdDev * $stdDeviation), 2),
            'middle' => round($sma, 2),
            'lower' => round($sma - ($stdDev * $stdDeviation), 2),
        ];
    }

    /**
     * Calculate all indicators for given OHLCV data
     */
    public function calculateAllIndicators(array $ohlcvData): array
    {
        // Extract close prices
        $closePrices = array_column($ohlcvData, 'close');

        return [
            'rsi' => $this->calculateRSI($closePrices),
            'macd' => $this->calculateMACD($closePrices),
            'bollinger_bands' => $this->calculateBollingerBands($closePrices),
            'ema_12' => $this->calculateEMA($closePrices, 12),
            'ema_26' => $this->calculateEMA($closePrices, 26),
            'ema_50' => $this->calculateEMA($closePrices, 50),
        ];
    }
}
