<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            // Add indexes for common queries
            $table->index('status', 'trades_status_index');
            $table->index('symbol', 'trades_symbol_index');
            $table->index(['status', 'symbol'], 'trades_status_symbol_index');
            $table->index('opened_at', 'trades_opened_at_index');
            $table->index('closed_at', 'trades_closed_at_index');
        });

        Schema::table('ai_decisions', function (Blueprint $table) {
            $table->index('symbol', 'ai_decisions_symbol_index');
            $table->index('executed', 'ai_decisions_executed_index');
            $table->index('analyzed_at', 'ai_decisions_analyzed_at_index');
        });

        Schema::table('market_data', function (Blueprint $table) {
            $table->index(['symbol', 'timeframe'], 'market_data_symbol_timeframe_index');
            $table->index('candle_time', 'market_data_candle_time_index');
        });

        Schema::table('settings', function (Blueprint $table) {
            // Key is already unique, but add index for faster lookups
            $table->index('key', 'settings_key_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex('trades_status_index');
            $table->dropIndex('trades_symbol_index');
            $table->dropIndex('trades_status_symbol_index');
            $table->dropIndex('trades_opened_at_index');
            $table->dropIndex('trades_closed_at_index');
        });

        Schema::table('ai_decisions', function (Blueprint $table) {
            $table->dropIndex('ai_decisions_symbol_index');
            $table->dropIndex('ai_decisions_executed_index');
            $table->dropIndex('ai_decisions_analyzed_at_index');
        });

        Schema::table('market_data', function (Blueprint $table) {
            $table->dropIndex('market_data_symbol_timeframe_index');
            $table->dropIndex('market_data_candle_time_index');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('settings_key_index');
        });
    }
};
