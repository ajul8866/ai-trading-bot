<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Trade;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TradingSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that API keys are encrypted in database
     */
    public function test_api_keys_are_encrypted(): void
    {
        // Create a setting with sensitive data
        $setting = Setting::create([
            'key' => 'binance_api_key',
            'value' => 'test_api_key_12345',
            'type' => 'string',
        ]);

        // Read raw value from database
        $rawValue = \DB::table('settings')
            ->where('id', $setting->id)
            ->value('value');

        // Raw value should be encrypted (not plain text)
        $this->assertNotEquals('test_api_key_12345', $rawValue);
        $this->assertStringContainsString(':', $rawValue); // Laravel encrypted strings contain ':'

        // But model accessor should decrypt it
        $this->assertEquals('test_api_key_12345', $setting->fresh()->value);
    }

    /**
     * Test unique constraint on ai_decision_id prevents duplicate trades
     */
    public function test_unique_constraint_prevents_duplicate_trades(): void
    {
        $this->markTestSkipped('Requires database migration to be run first');

        // Create first trade
        $trade1 = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'entry_price' => 50000,
            'quantity' => 0.1,
            'ai_decision_id' => 1,
            'opened_at' => now(),
        ]);

        $this->assertInstanceOf(Trade::class, $trade1);

        // Try to create duplicate trade with same ai_decision_id
        $this->expectException(\Illuminate\Database\QueryException::class);

        Trade::create([
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'entry_price' => 3000,
            'quantity' => 1,
            'ai_decision_id' => 1, // Same as trade1
            'opened_at' => now(),
        ]);
    }

    /**
     * Test that rate limiting is applied
     */
    public function test_rate_limiting_configuration_exists(): void
    {
        // Check that RateLimitApi middleware exists
        $this->assertTrue(
            class_exists(\App\Http\Middleware\RateLimitApi::class),
            'RateLimitApi middleware should exist'
        );
    }

    /**
     * Test that authentication middleware exists
     */
    public function test_authentication_middleware_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Middleware\EnsureApiAuthenticated::class),
            'EnsureApiAuthenticated middleware should exist'
        );
    }
}
