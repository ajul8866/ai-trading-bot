<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChartData>
 */
class ChartDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $open = fake()->randomFloat(8, 20000, 70000);
        $high = $open * fake()->randomFloat(4, 1.001, 1.02);
        $low = $open * fake()->randomFloat(4, 0.98, 0.999);
        $close = fake()->randomFloat(8, $low, $high);

        return [
            'symbol' => fake()->randomElement(['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT']),
            'timeframe' => fake()->randomElement(['5m', '15m', '30m', '1h']),
            'timestamp' => fake()->dateTimeBetween('-7 days', 'now'),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => fake()->randomFloat(8, 100, 10000),
        ];
    }
}
