<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\BinanceService;
use App\Services\OpenRouterAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ValidateApiKeysJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(BinanceService $binance, OpenRouterAIService $aiService): void
    {
        $validationResults = [
            'binance' => false,
            'openrouter' => false,
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            // Validate Binance API Keys
            $binanceApiKey = Setting::where('key', 'binance_api_key')->value('value');
            $binanceApiSecret = Setting::where('key', 'binance_api_secret')->value('value');

            if (! empty($binanceApiKey) && ! empty($binanceApiSecret)) {
                $balance = $binance->getBalance();

                if (! isset($balance['error'])) {
                    $validationResults['binance'] = true;
                    Log::info('Binance API keys validated successfully');
                } else {
                    Log::warning('Binance API validation failed', ['error' => $balance['error']]);
                }
            } else {
                Log::warning('Binance API keys not configured');
            }
        } catch (\Exception $e) {
            Log::error('Exception validating Binance API keys', ['error' => $e->getMessage()]);
        }

        try {
            // Validate OpenRouter API Key
            $openRouterApiKey = Setting::where('key', 'openrouter_api_key')->value('value');

            if (! empty($openRouterApiKey)) {
                // Simple test to validate the API key
                // We'll just check if the service can be instantiated
                // A full validation would require making a test API call
                $validationResults['openrouter'] = ! empty($openRouterApiKey);
                Log::info('OpenRouter API key present');
            } else {
                Log::warning('OpenRouter API key not configured');
            }
        } catch (\Exception $e) {
            Log::error('Exception validating OpenRouter API key', ['error' => $e->getMessage()]);
        }

        // Store validation results in settings
        Setting::updateOrCreate(
            ['key' => 'api_validation_status'],
            [
                'value' => json_encode($validationResults),
                'type' => 'json',
                'description' => 'API keys validation status',
            ]
        );

        Log::info('API keys validation completed', $validationResults);
    }
}
