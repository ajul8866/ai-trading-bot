<?php

namespace App\Services;

use App\DTOs\MarketAnalysisDTO;
use App\DTOs\TradingDecisionDTO;
use App\Interfaces\AIServiceInterface;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterAIService implements AIServiceInterface
{
    private string $apiKey;

    private string $model;

    private string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->apiKey = Setting::getValue('openrouter_api_key', '');
        $this->model = Setting::getValue('ai_model', 'anthropic/claude-3.5-sonnet');
    }

    public function analyzeAndDecide(MarketAnalysisDTO $marketData): TradingDecisionDTO
    {
        if (empty($this->apiKey)) {
            return $this->createFallbackDecision($marketData, 'OpenRouter API key not configured');
        }

        try {
            $prompt = $this->buildPrompt($marketData);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 8000,
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json('choices.0.message.content');

                return $this->parseAIResponse($marketData->symbol, $aiResponse);
            }

            Log::error('Failed to get AI decision', ['response' => $response->body()]);

            return $this->createFallbackDecision($marketData, 'Failed to get AI response');
        } catch (\Exception $e) {
            Log::error('Exception getting AI decision', ['error' => $e->getMessage()]);

            return $this->createFallbackDecision($marketData, $e->getMessage());
        }
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert cryptocurrency futures trading AI. Your role is to analyze market data and make informed trading decisions.

You must respond ONLY with a valid JSON object in this exact format:
{
    "decision": "BUY|SELL|HOLD|CLOSE",
    "confidence": 0-100,
    "reasoning": "Detailed explanation of your decision",
    "market_conditions": {
        "trend": "bullish|bearish|sideways",
        "volatility": "low|medium|high",
        "strength": "weak|moderate|strong"
    },
    "recommended_leverage": 1-5 (null for HOLD),
    "recommended_stop_loss": price (null for HOLD),
    "recommended_take_profit": price (null for HOLD),
    "risk_assessment": {
        "risk_level": "low|medium|high",
        "reward_ratio": 1.5-3.0
    }
}

Trading Rules:
1. Only suggest BUY/SELL if confidence >= 75%
2. Always suggest stop loss and take profit for BUY/SELL
3. Risk/Reward ratio must be at least 1.5:1
4. Consider multiple timeframes (5m, 15m, 30m, 1h)
5. Use technical indicators (RSI, MACD, Bollinger Bands)
6. Consider current market trend and volatility
7. Suggest HOLD if conditions are unclear or risky
8. Suggest CLOSE for open positions that should be exited

Respond ONLY with the JSON object, no additional text.
PROMPT;
    }

    private function buildPrompt(MarketAnalysisDTO $marketData): string
    {
        $ohlcvSummary = [];
        foreach ($marketData->ohlcvData as $timeframe => $data) {
            // Safety check: ensure data is not empty
            if (empty($data) || !is_array($data)) {
                continue;
            }

            // Get latest candle safely
            $dataArray = array_values($data); // Reindex to ensure numeric keys
            $latest = end($dataArray);
            $first = reset($dataArray);

            // Calculate price change safely
            $change = 0;
            if (count($dataArray) > 1 && isset($first['close']) && isset($latest['close']) && $first['close'] > 0) {
                $change = (($latest['close'] - $first['close']) / $first['close']) * 100;
            }

            $ohlcvSummary[$timeframe] = [
                'close' => $latest['close'] ?? 0,
                'change' => $change,
            ];
        }

        return json_encode([
            'symbol' => $marketData->symbol,
            'timeframes' => $marketData->timeframes,
            'price_action' => $ohlcvSummary,
            'indicators' => $marketData->indicators,
            'open_positions' => $marketData->openPositions,
            'account_balance' => $marketData->accountBalance,
            'risk_per_trade' => $marketData->riskPerTrade,
            'max_positions' => $marketData->maxPositions,
        ], JSON_PRETTY_PRINT);
    }

    private function parseAIResponse(string $symbol, string $aiResponse): TradingDecisionDTO
    {
        try {
            // Try to extract JSON from response
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return new TradingDecisionDTO(
                        symbol: $symbol,
                        decision: $data['decision'] ?? 'HOLD',
                        confidence: (float) ($data['confidence'] ?? 0),
                        reasoning: $data['reasoning'] ?? 'No reasoning provided',
                        marketConditions: $data['market_conditions'] ?? [],
                        recommendedLeverage: $data['recommended_leverage'] ?? null,
                        recommendedStopLoss: $data['recommended_stop_loss'] ?? null,
                        recommendedTakeProfit: $data['recommended_take_profit'] ?? null,
                        riskAssessment: $data['risk_assessment'] ?? null,
                    );
                }
            }

            Log::warning('Failed to parse AI response as JSON', ['response' => $aiResponse]);

            return $this->createFallbackDecision(null, 'Invalid JSON response from AI', $symbol);
        } catch (\Exception $e) {
            Log::error('Exception parsing AI response', ['error' => $e->getMessage()]);

            return $this->createFallbackDecision(null, $e->getMessage(), $symbol);
        }
    }

    private function createFallbackDecision(?MarketAnalysisDTO $marketData, string $reason, ?string $symbol = null): TradingDecisionDTO
    {
        return new TradingDecisionDTO(
            symbol: $symbol ?? $marketData?->symbol ?? 'UNKNOWN',
            decision: 'HOLD',
            confidence: 0,
            reasoning: "Fallback decision: {$reason}",
            marketConditions: ['error' => true],
            recommendedLeverage: null,
            recommendedStopLoss: null,
            recommendedTakeProfit: null,
            riskAssessment: ['risk_level' => 'high'],
        );
    }
}
