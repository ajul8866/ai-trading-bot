<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    // Bot Status
    public $bot_enabled = false;

    // API Keys
    public $binance_api_key = '';
    public $binance_api_secret = '';
    public $openrouter_api_key = '';

    // Trading Configuration
    public $trading_pairs = '';
    public $timeframes = '';
    public $analysis_interval = 180;

    // Risk Management
    public $max_positions = 5;
    public $risk_per_trade = 2;
    public $daily_loss_limit = 10;

    // AI Configuration
    public $ai_model = 'anthropic/claude-3.5-sonnet';
    public $min_confidence = 70;
    public $ai_prompt_system = '';
    public $ai_prompt_templates = '';
    public $ai_prompt_risk_profile = 'balanced';

    // Cache Configuration
    public $cache_ttl_prices = 5;
    public $cache_ttl_charts = 300;

    // UI Configuration
    public $ui_refresh_interval = 3;

    // UI State
    public $message = '';
    public $messageType = 'success';

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $settings = Setting::all()->keyBy('key');

        // Bot Status
        $this->bot_enabled = $settings->get('bot_enabled')?->value === 'true' || $settings->get('bot_enabled')?->value === '1' || $settings->get('bot_enabled')?->value === true;

        // API Keys
        $this->binance_api_key = $settings->get('binance_api_key')?->value ?? '';
        $this->binance_api_secret = $settings->get('binance_api_secret')?->value ?? '';
        $this->openrouter_api_key = $settings->get('openrouter_api_key')?->value ?? '';

        // Trading Configuration
        $tradingPairs = $settings->get('trading_pairs')?->value ?? '[]';
        $this->trading_pairs = is_array($tradingPairs) ? implode(',', $tradingPairs) : implode(',', json_decode($tradingPairs, true) ?? []);

        $timeframes = $settings->get('timeframes')?->value ?? '[]';
        $this->timeframes = is_array($timeframes) ? implode(',', $timeframes) : implode(',', json_decode($timeframes, true) ?? []);

        $this->analysis_interval = (int) ($settings->get('analysis_interval')?->value ?? 180);

        // Risk Management
        $this->max_positions = (int) ($settings->get('max_positions')?->value ?? 5);
        $this->risk_per_trade = (float) ($settings->get('risk_per_trade')?->value ?? 2);
        $this->daily_loss_limit = (float) ($settings->get('daily_loss_limit')?->value ?? 10);

        // AI Configuration
        $this->ai_model = $settings->get('ai_model')?->value ?? 'anthropic/claude-3.5-sonnet';
        $this->min_confidence = (int) ($settings->get('min_confidence')?->value ?? 70);
        $this->ai_prompt_system = $settings->get('ai_prompt_system')?->value ?? '';

        $promptTemplates = $settings->get('ai_prompt_templates')?->value ?? '{}';
        $this->ai_prompt_templates = is_array($promptTemplates) ? json_encode($promptTemplates, JSON_PRETTY_PRINT) : (is_string($promptTemplates) ? json_encode(json_decode($promptTemplates, true), JSON_PRETTY_PRINT) : '{}');

        $this->ai_prompt_risk_profile = $settings->get('ai_prompt_risk_profile')?->value ?? 'balanced';

        // Cache Configuration
        $this->cache_ttl_prices = (int) ($settings->get('cache_ttl_prices')?->value ?? 5);
        $this->cache_ttl_charts = (int) ($settings->get('cache_ttl_charts')?->value ?? 300);

        // UI Configuration
        $this->ui_refresh_interval = (int) ($settings->get('ui_refresh_interval')?->value ?? 3);
    }

    public function save()
    {
        try {
            // Bot Status
            Setting::where('key', 'bot_enabled')->update(['value' => $this->bot_enabled ? 'true' : 'false']);

            // API Keys
            Setting::where('key', 'binance_api_key')->update(['value' => $this->binance_api_key]);
            Setting::where('key', 'binance_api_secret')->update(['value' => $this->binance_api_secret]);
            Setting::where('key', 'openrouter_api_key')->update(['value' => $this->openrouter_api_key]);

            // Trading Configuration
            $tradingPairsArray = array_map('trim', explode(',', $this->trading_pairs));
            Setting::where('key', 'trading_pairs')->update(['value' => json_encode($tradingPairsArray)]);

            $timeframesArray = array_map('trim', explode(',', $this->timeframes));
            Setting::where('key', 'timeframes')->update(['value' => json_encode($timeframesArray)]);

            Setting::where('key', 'analysis_interval')->update(['value' => $this->analysis_interval]);

            // Risk Management
            Setting::where('key', 'max_positions')->update(['value' => $this->max_positions]);
            Setting::where('key', 'risk_per_trade')->update(['value' => $this->risk_per_trade]);
            Setting::where('key', 'daily_loss_limit')->update(['value' => $this->daily_loss_limit]);

            // AI Configuration
            Setting::where('key', 'ai_model')->update(['value' => $this->ai_model]);
            Setting::where('key', 'min_confidence')->update(['value' => $this->min_confidence]);
            Setting::where('key', 'ai_prompt_system')->update(['value' => $this->ai_prompt_system]);
            Setting::where('key', 'ai_prompt_templates')->update(['value' => $this->ai_prompt_templates]);
            Setting::where('key', 'ai_prompt_risk_profile')->update(['value' => $this->ai_prompt_risk_profile]);

            // Cache Configuration
            Setting::where('key', 'cache_ttl_prices')->update(['value' => $this->cache_ttl_prices]);
            Setting::where('key', 'cache_ttl_charts')->update(['value' => $this->cache_ttl_charts]);

            // UI Configuration
            Setting::where('key', 'ui_refresh_interval')->update(['value' => $this->ui_refresh_interval]);

            $this->message = 'All settings saved successfully!';
            $this->messageType = 'success';
        } catch (\Exception $e) {
            $this->message = 'Error saving settings: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function testBinanceConnection()
    {
        try {
            $service = app(\App\Services\BinanceService::class);
            $price = $service->getCurrentPrice('BTCUSDT');

            if ($price > 0) {
                $this->message = "Binance connection successful! BTC Price: $" . number_format($price, 2);
                $this->messageType = 'success';
            } else {
                $this->message = "Binance connection failed - no price data returned";
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            $this->message = "Binance connection error: " . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function testOpenRouterConnection()
    {
        if (empty($this->openrouter_api_key)) {
            $this->message = "OpenRouter API key is empty";
            $this->messageType = 'error';
            return;
        }

        $this->message = "OpenRouter API key is configured. Test by analyzing a trading pair.";
        $this->messageType = 'success';
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
