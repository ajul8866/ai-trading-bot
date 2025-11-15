<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketData>
 */
class MarketDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'SOLUSDT'];
        $timeframes = ['5m', '15m', '30m', '1h'];
        $open = fake()->randomFloat(8, 1000, 50000);
        $high = $open * fake()->randomFloat(2, 1.01, 1.05);
        $low = $open * fake()->randomFloat(2, 0.95, 0.99);
        $close = fake()->randomFloat(8, $low, $high);

        return [
            'symbol' => fake()->randomElement($symbols),
            'timeframe' => fake()->randomElement($timeframes),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => fake()->randomFloat(8, 100, 10000),
            'indicators' => [
                'rsi' => fake()->randomFloat(2, 20, 80),
                'macd' => fake()->randomFloat(2, -10, 10),
                'bb_upper' => $high * 1.02,
                'bb_middle' => $close,
                'bb_lower' => $low * 0.98,
            ],
            'candle_time' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
