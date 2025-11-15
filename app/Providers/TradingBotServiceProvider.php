<?php

namespace App\Providers;

use App\Interfaces\AIServiceInterface;
use App\Interfaces\ExchangeInterface;
use App\Services\BinanceService;
use App\Services\OpenRouterAIService;
use Illuminate\Support\ServiceProvider;

class TradingBotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Exchange Interface to Binance Service
        $this->app->bind(ExchangeInterface::class, BinanceService::class);

        // Bind AI Service Interface to OpenRouter AI Service
        $this->app->bind(AIServiceInterface::class, OpenRouterAIService::class);

        // Register services as singletons for better performance
        $this->app->singleton(BinanceService::class, function ($app) {
            return new BinanceService;
        });

        $this->app->singleton(OpenRouterAIService::class, function ($app) {
            return new OpenRouterAIService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
