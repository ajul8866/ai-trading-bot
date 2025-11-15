<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // API Keys
            ['key' => 'binance_api_key', 'value' => '', 'type' => 'string', 'description' => 'Binance API Key'],
            ['key' => 'binance_api_secret', 'value' => '', 'type' => 'string', 'description' => 'Binance API Secret'],
            ['key' => 'openrouter_api_key', 'value' => '', 'type' => 'string', 'description' => 'OpenRouter API Key for LLM'],

            // Risk Management
            ['key' => 'max_positions', 'value' => '5', 'type' => 'integer', 'description' => 'Maximum concurrent positions'],
            ['key' => 'risk_per_trade', 'value' => '2', 'type' => 'integer', 'description' => 'Max risk per trade (%)'],
            ['key' => 'daily_loss_limit', 'value' => '10', 'type' => 'integer', 'description' => 'Daily loss limit (%)'],

            // Trading Configuration
            ['key' => 'trading_pairs', 'value' => json_encode([
                'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'SOLUSDT',
                'XRPUSDT', 'DOTUSDT', 'DOGEUSDT', 'MATICUSDT', 'LTCUSDT',
                'AVAXUSDT', 'LINKUSDT', 'ATOMUSDT', 'NEARUSDT', 'APTUSDT'
            ]), 'type' => 'json', 'description' => 'Active trading pairs (15 pairs)'],
            ['key' => 'timeframes', 'value' => json_encode(['5m', '15m', '30m', '1h']), 'type' => 'json', 'description' => 'Analysis timeframes'],
            ['key' => 'analysis_interval', 'value' => '180', 'type' => 'integer', 'description' => 'Analysis interval in seconds (3 minutes)'],

            // AI Configuration
            ['key' => 'ai_model', 'value' => 'anthropic/claude-3.5-sonnet', 'type' => 'string', 'description' => 'AI model for decision making'],
            ['key' => 'min_confidence', 'value' => '70', 'type' => 'integer', 'description' => 'Minimum confidence % to execute trade'],

            // Bot Status
            ['key' => 'bot_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable/disable trading bot'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
