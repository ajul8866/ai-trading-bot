<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settings = [
            ['key' => 'binance_api_key', 'value' => '', 'type' => 'string', 'description' => 'Binance API Key'],
            ['key' => 'binance_api_secret', 'value' => '', 'type' => 'string', 'description' => 'Binance API Secret'],
            ['key' => 'openrouter_api_key', 'value' => '', 'type' => 'string', 'description' => 'OpenRouter API Key'],
            ['key' => 'max_positions', 'value' => '5', 'type' => 'integer', 'description' => 'Maximum concurrent positions'],
            ['key' => 'risk_per_trade', 'value' => '2', 'type' => 'integer', 'description' => 'Max risk per trade (%)'],
            ['key' => 'daily_loss_limit', 'value' => '10', 'type' => 'integer', 'description' => 'Daily loss limit (%)'],
            ['key' => 'trading_pairs', 'value' => json_encode(['BTCUSDT', 'ETHUSDT', 'BNBUSDT']), 'type' => 'json', 'description' => 'Active trading pairs'],
        ];

        $setting = fake()->randomElement($settings);

        return [
            'key' => $setting['key'],
            'value' => $setting['value'],
            'type' => $setting['type'],
            'description' => $setting['description'],
        ];
    }
}
