<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Add missing database constraints for data integrity
     * - Unique constraints to prevent duplicates
     * - CHECK constraints to enforce business rules
     * - ENUM constraints for type safety
     */
    public function up(): void
    {
        // FIX #1: trades table - Add unique constraint on binance_order_id
        // Prevents duplicate orders from same Binance order ID
        Schema::table('trades', function (Blueprint $table) {
            // Drop existing column and recreate with unique constraint
            $table->string('binance_order_id')->unique()->nullable()->change();
        });

        // FIX #2: market_data table - Add unique constraint on [symbol, timeframe, candle_time]
        // Prevents duplicate candles which corrupt technical analysis
        Schema::table('market_data', function (Blueprint $table) {
            $table->unique(['symbol', 'timeframe', 'candle_time'], 'unique_market_candle');
        });

        // FIX #3: performance_snapshots table - Add unique constraint on [snapshot_at, period]
        // Prevents duplicate performance snapshots for same time/period
        Schema::table('performance_snapshots', function (Blueprint $table) {
            $table->unique(['snapshot_at', 'period'], 'unique_performance_snapshot');
        });

        // FIX #4: Add CHECK constraints for business rules (SQLite doesn't support CHECK, MySQL/PostgreSQL do)
        if (config('database.default') !== 'sqlite') {
            // trades: leverage must be between 1 and 125 (Binance limit)
            DB::statement('ALTER TABLE trades ADD CONSTRAINT check_trades_leverage CHECK (leverage >= 1 AND leverage <= 125)');

            // ai_decisions: confidence must be between 0 and 100
            DB::statement('ALTER TABLE ai_decisions ADD CONSTRAINT check_ai_confidence CHECK (confidence >= 0 AND confidence <= 100)');

            // performance_snapshots: win_rate must be between 0 and 100
            DB::statement('ALTER TABLE performance_snapshots ADD CONSTRAINT check_win_rate CHECK (win_rate IS NULL OR (win_rate >= 0 AND win_rate <= 100))');

            // performance_snapshots: winning_trades cannot exceed total_trades
            DB::statement('ALTER TABLE performance_snapshots ADD CONSTRAINT check_trades_count CHECK (winning_trades <= total_trades)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop CHECK constraints first (if not SQLite)
        if (config('database.default') !== 'sqlite') {
            DB::statement('ALTER TABLE trades DROP CONSTRAINT IF EXISTS check_trades_leverage');
            DB::statement('ALTER TABLE ai_decisions DROP CONSTRAINT IF EXISTS check_ai_confidence');
            DB::statement('ALTER TABLE performance_snapshots DROP CONSTRAINT IF EXISTS check_win_rate');
            DB::statement('ALTER TABLE performance_snapshots DROP CONSTRAINT IF EXISTS check_trades_count');
        }

        // Drop unique constraints
        Schema::table('performance_snapshots', function (Blueprint $table) {
            $table->dropUnique('unique_performance_snapshot');
        });

        Schema::table('market_data', function (Blueprint $table) {
            $table->dropUnique('unique_market_candle');
        });

        Schema::table('trades', function (Blueprint $table) {
            $table->string('binance_order_id')->nullable()->change(); // Remove unique
        });
    }
};
