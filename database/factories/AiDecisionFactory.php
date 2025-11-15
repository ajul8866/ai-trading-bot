<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiDecision>
 */
class AiDecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'SOLUSDT'];
        $decision = fake()->randomElement(['BUY', 'SELL', 'HOLD', 'CLOSE']);

        return [
            'symbol' => fake()->randomElement($symbols),
            'timeframes_analyzed' => ['5m', '15m', '30m', '1h'],
            'market_conditions' => [
                'trend' => fake()->randomElement(['bullish', 'bearish', 'sideways']),
                'volatility' => fake()->randomElement(['low', 'medium', 'high']),
                'volume' => fake()->randomElement(['increasing', 'decreasing', 'stable']),
            ],
            'decision' => $decision,
            'confidence' => fake()->randomFloat(2, 60, 95),
            'reasoning' => fake()->sentence(20),
            'risk_assessment' => [
                'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
                'reward_ratio' => fake()->randomFloat(2, 1.5, 3.0),
            ],
            'recommended_leverage' => $decision === 'HOLD' ? null : fake()->numberBetween(1, 5),
            'recommended_stop_loss' => $decision === 'HOLD' ? null : fake()->randomFloat(8, 1000, 50000),
            'recommended_take_profit' => $decision === 'HOLD' ? null : fake()->randomFloat(8, 1000, 50000),
            'executed' => fake()->boolean(30),
            'execution_error' => null,
            'analyzed_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
