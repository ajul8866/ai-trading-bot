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
        // Add indexes to trades table for performance optimization
        Schema::table('trades', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('symbol');
            $table->index('status');
            $table->index('closed_at');
        });

        // Add indexes to market_data table for performance optimization
        Schema::table('market_data', function (Blueprint $table) {
            $table->index('symbol');
            $table->index('timeframe');
            $table->index(['symbol', 'timeframe']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from trades table
        Schema::table('trades', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['symbol']);
            $table->dropIndex(['status']);
            $table->dropIndex(['closed_at']);
        });

        // Remove indexes from market_data table
        Schema::table('market_data', function (Blueprint $table) {
            $table->dropIndex(['symbol']);
            $table->dropIndex(['timeframe']);
            $table->dropIndex(['symbol', 'timeframe']);
        });
    }
};
