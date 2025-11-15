<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'SOLUSDT'];
        $side = fake()->randomElement(['LONG', 'SHORT']);
        $entryPrice = fake()->randomFloat(8, 1000, 50000);
        $stopLoss = $side === 'LONG'
            ? $entryPrice * 0.98  // 2% below entry for LONG
            : $entryPrice * 1.02; // 2% above entry for SHORT
        $takeProfit = $side === 'LONG'
            ? $entryPrice * 1.04  // 4% above entry for LONG
            : $entryPrice * 0.96; // 4% below entry for SHORT

        return [
            'symbol' => fake()->randomElement($symbols),
            'side' => $side,
            'entry_price' => $entryPrice,
            'exit_price' => null,
            'quantity' => fake()->randomFloat(8, 0.001, 1),
            'leverage' => fake()->numberBetween(1, 10),
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'status' => fake()->randomElement(['OPEN', 'CLOSED', 'CANCELLED']),
            'pnl' => null,
            'pnl_percentage' => null,
            'binance_order_id' => fake()->unique()->numerify('##########'),
            'ai_decision_id' => null,
            'opened_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'closed_at' => null,
        ];
    }
}
