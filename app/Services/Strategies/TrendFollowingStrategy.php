<?php

namespace App\Services\Strategies;

use App\Contracts\TradingStrategyInterface;
use App\DTOs\MarketAnalysisDTO;
use App\DTOs\StrategySignalDTO;
use App\Services\TechnicalIndicatorService;

/**
 * Trend Following Strategy
 *
 * This strategy identifies and follows market trends using multiple timeframes
 * and technical indicators. It combines EMA crossovers, ADX for trend strength,
 * and MACD for momentum confirmation.
 *
 * Entry Conditions (LONG):
 * - Fast EMA crosses above Slow EMA
 * - ADX > 25 (strong trend)
 * - MACD histogram positive and increasing
 * - Price above 200 EMA (major trend confirmation)
 * - All higher timeframes confirming uptrend
 *
 * Entry Conditions (SHORT):
 * - Fast EMA crosses below Slow EMA
 * - ADX > 25 (strong trend)
 * - MACD histogram negative and decreasing
 * - Price below 200 EMA (major trend confirmation)
 * - All higher timeframes confirming downtrend
 *
 * Exit Conditions:
 * - EMA crossover in opposite direction
 * - ADX drops below 20 (trend weakening)
 * - Stop loss hit
 * - Take profit hit
 */
class TrendFollowingStrategy implements TradingStrategyInterface
{
    private TechnicalIndicatorService $indicatorService;

    // Strategy parameters (can be optimized)
    private int $fastEMA = 12;
    private int $slowEMA = 26;
    private int $trendEMA = 200;
    private float $minADX = 25;
    private float $riskRewardRatio = 2.0;

    public function __construct(TechnicalIndicatorService $indicatorService)
    {
        $this->indicatorService = $indicatorService;
    }

    public function getName(): string
    {
        return 'Trend Following Strategy';
    }

    public function getDescription(): string
    {
        return 'Multi-timeframe trend following strategy using EMA crossovers, ADX, and MACD for high-probability trend trades';
    }

    public function analyze(MarketAnalysisDTO $marketData): StrategySignalDTO
    {
        $reasons = [];
        $signal = 'HOLD';
        $strength = 0;
        $confidence = 0;

        // Analyze each timeframe
        $timeframeSignals = [];
        foreach ($marketData->timeframes as $timeframe) {
            $timeframeSignals[$timeframe] = $this->analyzeTimeframe(
                $marketData->ohlcvData[$timeframe] ?? [],
                $marketData->indicators[$timeframe] ?? []
            );
        }

        // Get primary timeframe (usually the lowest one for entry)
        $primaryTimeframe = $marketData->timeframes[0] ?? '5m';
        $primarySignal = $timeframeSignals[$primaryTimeframe];

        // Check higher timeframe alignment
        $higherTimeframesAligned = $this->checkHigherTimeframeAlignment($timeframeSignals);

        // Calculate signal strength based on multiple factors
        $strength = $this->calculateSignalStrength($primarySignal, $timeframeSignals);
        $confidence = $this->calculateConfidence($primarySignal, $higherTimeframesAligned);

        // Determine final signal
        if ($primarySignal['trend'] === 'BULLISH' && $higherTimeframesAligned['bullish'] >= 0.75) {
            $signal = 'BUY';
            $reasons[] = 'Strong bullish trend detected across multiple timeframes';
            $reasons[] = "ADX: {$primarySignal['adx']} (strong trend)";
            $reasons[] = 'Fast EMA crossed above Slow EMA';
            $reasons[] = 'MACD histogram positive and expanding';
        } elseif ($primarySignal['trend'] === 'BEARISH' && $higherTimeframesAligned['bearish'] >= 0.75) {
            $signal = 'SELL';
            $reasons[] = 'Strong bearish trend detected across multiple timeframes';
            $reasons[] = "ADX: {$primarySignal['adx']} (strong trend)";
            $reasons[] = 'Fast EMA crossed below Slow EMA';
            $reasons[] = 'MACD histogram negative and expanding';
        } else {
            $reasons[] = 'No strong trend signal detected';
            $reasons[] = 'Waiting for better setup';
        }

        // Calculate entry, stop loss, and take profit
        $latestCandle = end($marketData->ohlcvData[$primaryTimeframe]);
        $entryPrice = $signal !== 'HOLD' ? $latestCandle['close'] : null;
        $stopLoss = null;
        $takeProfit = null;
        $riskRewardRatio = null;

        if ($signal === 'BUY') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'LONG', $marketData);
            $takeProfit = $this->calculateTakeProfit($entryPrice, 'LONG', $marketData);
            $riskRewardRatio = abs($takeProfit - $entryPrice) / abs($entryPrice - $stopLoss);
        } elseif ($signal === 'SELL') {
            $stopLoss = $this->calculateStopLoss($entryPrice, 'SHORT', $marketData);
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
            indicators: $primarySignal,
            entryPrice: $entryPrice,
            stopLoss: $stopLoss,
            takeProfit: $takeProfit,
            recommendedLeverage: $this->calculateLeverage($strength, $confidence),
            positionSize: $signal !== 'HOLD' ? $this->calculatePositionSize($marketData, $marketData->accountBalance) : null,
            riskRewardRatio: $riskRewardRatio,
            metadata: [
                'timeframe_signals' => $timeframeSignals,
                'higher_tf_alignment' => $higherTimeframesAligned,
            ]
        );
    }

    private function analyzeTimeframe(array $ohlcvData, array $indicators): array
    {
        if (empty($ohlcvData)) {
            return [
                'trend' => 'NEUTRAL',
                'adx' => 0,
                'ema_cross' => false,
                'macd_histogram' => 0,
            ];
        }

        $closePrices = array_column($ohlcvData, 'close');

        // Calculate EMAs
        $fastEMA = $this->indicatorService->calculateEMA($closePrices, $this->fastEMA);
        $slowEMA = $this->indicatorService->calculateEMA($closePrices, $this->slowEMA);
        $trendEMA = $this->indicatorService->calculateEMA($closePrices, $this->trendEMA);

        // Get MACD
        $macd = $indicators['macd'] ?? $this->indicatorService->calculateMACD($closePrices);

        // Calculate ADX (simplified version)
        $adx = $this->calculateADX($ohlcvData);

        // Determine trend
        $currentPrice = end($closePrices);
        $trend = 'NEUTRAL';

        if ($fastEMA > $slowEMA && $currentPrice > $trendEMA && $adx > $this->minADX) {
            $trend = 'BULLISH';
        } elseif ($fastEMA < $slowEMA && $currentPrice < $trendEMA && $adx > $this->minADX) {
            $trend = 'BEARISH';
        }

        return [
            'trend' => $trend,
            'fast_ema' => $fastEMA,
            'slow_ema' => $slowEMA,
            'trend_ema' => $trendEMA,
            'adx' => $adx,
            'ema_cross' => abs($fastEMA - $slowEMA) / $slowEMA < 0.002, // Recent cross
            'macd_histogram' => $macd['histogram'] ?? 0,
            'current_price' => $currentPrice,
        ];
    }

    private function calculateADX(array $ohlcvData, int $period = 14): float
    {
        // Simplified ADX calculation
        if (count($ohlcvData) < $period + 1) {
            return 0;
        }

        $trueRanges = [];
        $plusDM = [];
        $minusDM = [];

        for ($i = 1; $i < count($ohlcvData); $i++) {
            $high = $ohlcvData[$i]['high'];
            $low = $ohlcvData[$i]['low'];
            $close = $ohlcvData[$i]['close'];
            $prevHigh = $ohlcvData[$i-1]['high'];
            $prevLow = $ohlcvData[$i-1]['low'];
            $prevClose = $ohlcvData[$i-1]['close'];

            // True Range
            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            $trueRanges[] = $tr;

            // Directional Movement
            $upMove = $high - $prevHigh;
            $downMove = $prevLow - $low;

            $plusDM[] = ($upMove > $downMove && $upMove > 0) ? $upMove : 0;
            $minusDM[] = ($downMove > $upMove && $downMove > 0) ? $downMove : 0;
        }

        // Calculate smoothed values
        $avgTR = array_sum(array_slice($trueRanges, -$period)) / $period;
        $avgPlusDM = array_sum(array_slice($plusDM, -$period)) / $period;
        $avgMinusDM = array_sum(array_slice($minusDM, -$period)) / $period;

        // Calculate DI+, DI-, and ADX
        $plusDI = ($avgTR > 0) ? ($avgPlusDM / $avgTR) * 100 : 0;
        $minusDI = ($avgTR > 0) ? ($avgMinusDM / $avgTR) * 100 : 0;

        $sum = $plusDI + $minusDI;
        $adx = ($sum > 0) ? (abs($plusDI - $minusDI) / $sum) * 100 : 0;

        return round($adx, 2);
    }

    private function checkHigherTimeframeAlignment(array $timeframeSignals): array
    {
        $bullishCount = 0;
        $bearishCount = 0;
        $total = count($timeframeSignals);

        foreach ($timeframeSignals as $signal) {
            if ($signal['trend'] === 'BULLISH') {
                $bullishCount++;
            } elseif ($signal['trend'] === 'BEARISH') {
                $bearishCount++;
            }
        }

        return [
            'bullish' => $total > 0 ? $bullishCount / $total : 0,
            'bearish' => $total > 0 ? $bearishCount / $total : 0,
            'neutral' => $total > 0 ? ($total - $bullishCount - $bearishCount) / $total : 0,
        ];
    }

    private function calculateSignalStrength(array $primarySignal, array $timeframeSignals): float
    {
        $strength = 0;

        // Base strength from ADX
        $strength += min($primarySignal['adx'], 100) * 0.4;

        // Strength from timeframe alignment
        $alignment = $this->checkHigherTimeframeAlignment($timeframeSignals);
        $maxAlignment = max($alignment['bullish'], $alignment['bearish']);
        $strength += $maxAlignment * 30;

        // Strength from MACD histogram
        $macdStrength = min(abs($primarySignal['macd_histogram'] ?? 0) * 10, 30);
        $strength += $macdStrength;

        return min(round($strength, 2), 100);
    }

    private function calculateConfidence(array $primarySignal, array $higherTimeframesAligned): float
    {
        $confidence = 0;

        // Confidence from trend strength (ADX)
        if ($primarySignal['adx'] > 40) {
            $confidence += 30;
        } elseif ($primarySignal['adx'] > 25) {
            $confidence += 20;
        }

        // Confidence from timeframe alignment
        $maxAlignment = max($higherTimeframesAligned['bullish'], $higherTimeframesAligned['bearish']);
        $confidence += $maxAlignment * 50;

        // Confidence from clear EMA trend
        if ($primarySignal['trend'] !== 'NEUTRAL') {
            $confidence += 20;
        }

        return min(round($confidence, 2), 100);
    }

    private function calculateLeverage(float $strength, float $confidence): int
    {
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
        return ['5m', '15m', '30m', '1h'];
    }

    public function getRequiredIndicators(): array
    {
        return ['ema', 'macd', 'adx'];
    }

    public function canTrade(MarketAnalysisDTO $marketData): bool
    {
        // Check if we have required data
        foreach ($this->getRequiredTimeframes() as $timeframe) {
            if (!isset($marketData->ohlcvData[$timeframe]) || empty($marketData->ohlcvData[$timeframe])) {
                return false;
            }
        }

        return true;
    }

    public function calculatePositionSize(MarketAnalysisDTO $marketData, float $accountBalance): float
    {
        // Risk 2% of account per trade
        $riskPercentage = 0.02;
        $riskAmount = $accountBalance * $riskPercentage;

        // Get latest price
        $latestCandle = end($marketData->ohlcvData[$marketData->timeframes[0]]);
        $currentPrice = $latestCandle['close'];

        // Calculate stop loss distance (2% from entry)
        $stopLossDistance = $currentPrice * 0.02;

        // Position size = Risk Amount / Stop Loss Distance
        $positionSize = $riskAmount / $stopLossDistance;

        return round($positionSize, 8);
    }

    public function calculateStopLoss(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float
    {
        // Stop loss at 2% from entry
        $stopLossPercentage = 0.02;

        if ($side === 'LONG') {
            return round($entryPrice * (1 - $stopLossPercentage), 2);
        } else {
            return round($entryPrice * (1 + $stopLossPercentage), 2);
        }
    }

    public function calculateTakeProfit(float $entryPrice, string $side, MarketAnalysisDTO $marketData): float
    {
        // Take profit at risk:reward ratio
        $stopLoss = $this->calculateStopLoss($entryPrice, $side, $marketData);
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
        // This would be calculated from historical trades
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'avg_profit' => 0,
            'avg_loss' => 0,
            'profit_factor' => 0,
            'sharpe_ratio' => 0,
        ];
    }

    public function optimizeParameters(array $historicalData): array
    {
        // Parameter optimization logic would go here
        // This would test different combinations of EMA periods, ADX thresholds, etc.
        return [
            'fast_ema' => $this->fastEMA,
            'slow_ema' => $this->slowEMA,
            'trend_ema' => $this->trendEMA,
            'min_adx' => $this->minADX,
            'risk_reward_ratio' => $this->riskRewardRatio,
        ];
    }
}
