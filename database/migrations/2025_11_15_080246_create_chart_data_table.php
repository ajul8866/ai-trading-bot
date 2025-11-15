<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_data', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->index();
            $table->string('timeframe', 10)->index();
            $table->timestamp('timestamp')->index();

            // OHLCV
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 20, 8);

            // Additional Metadata
            $table->integer('number_of_trades')->nullable();
            $table->decimal('taker_buy_volume', 20, 8)->nullable();
            $table->decimal('taker_buy_quote_volume', 20, 8)->nullable();

            $table->timestamps();

            // Unique composite index
            $table->unique(['symbol', 'timeframe', 'timestamp'], 'chart_data_unique');

            // Optimization indexes
            $table->index(['symbol', 'timeframe', 'timestamp'], 'chart_data_query');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_data');
    }
};
