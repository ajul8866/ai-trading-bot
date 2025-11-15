<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerformanceSnapshot>
 */
class PerformanceSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalTrades = fake()->numberBetween(10, 100);
        $winningTrades = fake()->numberBetween(5, $totalTrades);
        $winRate = ($totalTrades > 0) ? ($winningTrades / $totalTrades) * 100 : 0;

        return [
            'snapshot_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'period' => fake()->randomElement(['hourly', 'daily']),
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'total_pnl' => fake()->randomFloat(8, -1000, 5000),
            'sharpe_ratio' => fake()->randomFloat(4, -1, 3),
            'sortino_ratio' => fake()->randomFloat(4, -1, 4),
            'max_drawdown' => fake()->randomFloat(4, 0, 50),
            'win_rate' => $winRate,
            'avg_win' => fake()->randomFloat(8, 50, 500),
            'avg_loss' => fake()->randomFloat(8, 30, 300),
        ];
    }
}
