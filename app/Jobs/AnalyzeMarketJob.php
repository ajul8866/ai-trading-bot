<?php

namespace App\Jobs;

use App\DTOs\MarketAnalysisDTO;
use App\Models\AiDecision;
use App\Models\Setting;
use App\Models\Trade;
use App\Services\BinanceService;
use App\Services\OpenRouterAIService;
use App\Services\RiskManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyzeMarketJob implements ShouldQueue
{
    use Queueable;

    /**
     * CRITICAL FIX: Add retry configuration for reliability
     */
    public int $tries = 3; // Retry up to 3 times
    public int $timeout = 120; // 2 minutes timeout for AI analysis

    /**
     * Exponential backoff delays (in seconds)
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Wait 10s, 30s, 60s between retries
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $symbol,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        BinanceService $binanceService,
        OpenRouterAIService $aiService,
        RiskManagementService $riskService
    ): void {
        try {
            Log::info('Analyzing market for symbol', ['symbol' => $this->symbol]);

            // Check if bot is enabled
            $botEnabled = Setting::where('key', 'bot_enabled')->value('value') === 'true';
            if (! $botEnabled) {
                Log::info('Bot is disabled, skipping analysis');

                return;
            }

            // Check risk limits
            if ($riskService->isDailyLossLimitReached()) {
                Log::warning('Daily loss limit reached, skipping analysis');

                return;
            }

            // Get timeframes from settings
            $timeframes = json_decode(Setting::where('key', 'timeframes')->value('value') ?? '[]', true);
            if (empty($timeframes)) {
                $timeframes = ['5m', '15m', '30m', '1h'];
            }

            // Collect market data for all timeframes
            $multiTimeframeData = [];
            $allIndicators = [];

            foreach ($timeframes as $timeframe) {
                $cacheKey = "market_data:{$this->symbol}:{$timeframe}";
                $cachedData = Cache::get($cacheKey);

                if ($cachedData) {
                    $multiTimeframeData[$timeframe] = $cachedData['ohlcv'];
                    $allIndicators[$timeframe] = $cachedData['indicators'];
                } else {
                    // If not cached, dispatch fetch job and skip this analysis
                    Log::info('Market data not in cache, dispatching fetch job', [
                        'symbol' => $this->symbol,
                        'timeframe' => $timeframe,
                    ]);
                    FetchMarketDataJob::dispatch($this->symbol, $timeframe);

                    return;
                }
            }

            // Get account information
            $accountBalance = $binanceService->getAccountBalance();

            $openPositions = Trade::where('status', 'OPEN')
                ->where('symbol', $this->symbol)
                ->get()
                ->toArray();

            $maxPositions = (int) Setting::where('key', 'max_positions')->value('value') ?? 5;
            $riskPerTrade = (float) Setting::where('key', 'risk_per_trade')->value('value') ?? 2;
            $dailyLossLimit = (float) Setting::where('key', 'daily_loss_limit')->value('value') ?? 10;

            // Prepare market analysis DTO
            $marketAnalysis = new MarketAnalysisDTO(
                symbol: $this->symbol,
                timeframes: $timeframes,
                ohlcvData: $multiTimeframeData,
                indicators: $allIndicators,
                openPositions: $openPositions,
                accountBalance: $accountBalance,
                maxPositions: $maxPositions,
                riskPerTrade: $riskPerTrade,
                dailyLossLimit: $dailyLossLimit,
            );

            // Get AI decision
            Log::info('Requesting AI analysis', ['symbol' => $this->symbol]);
            $decision = $aiService->analyzeAndDecide($marketAnalysis);

            // Store AI decision in database
            $aiDecision = AiDecision::create([
                'symbol' => $this->symbol,
                'timeframes_analyzed' => $timeframes,
                'market_conditions' => $decision->marketConditions,
                'decision' => $decision->decision,
                'confidence' => $decision->confidence,
                'reasoning' => $decision->reasoning,
                'risk_assessment' => $decision->riskAssessment,
                'recommended_leverage' => $decision->recommendedLeverage,
                'recommended_stop_loss' => $decision->recommendedStopLoss,
                'recommended_take_profit' => $decision->recommendedTakeProfit,
                'executed' => false,
                'analyzed_at' => now(),
            ]);

            Log::info('AI decision received', [
                'symbol' => $this->symbol,
                'decision' => $decision->decision,
                'confidence' => $decision->confidence,
            ]);

            // Execute trade if decision meets criteria
            $minConfidence = (int) Setting::where('key', 'min_confidence')->value('value') ?? 70;

            // For CLOSE decisions, skip max position check (we're closing, not opening)
            // For BUY/SELL, check if we can open a new position
            $canExecute = $decision->shouldExecute($minConfidence) &&
                         ($decision->decision === 'CLOSE' || $riskService->canOpenPosition());

            if ($canExecute) {
                Log::info('Decision meets execution criteria, dispatching execution job', [
                    'symbol' => $this->symbol,
                    'decision' => $decision->decision,
                ]);

                ExecuteTradeJob::dispatch($aiDecision->id);
            } else {
                Log::info('Decision does not meet execution criteria', [
                    'symbol' => $this->symbol,
                    'decision' => $decision->decision,
                    'confidence' => $decision->confidence,
                    'min_confidence' => $minConfidence,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error analyzing market', [
                'symbol' => $this->symbol,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
