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
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->string('symbol'); // e.g., 'BTCUSDT'
            $table->string('timeframe'); // '5m', '15m', '30m', '1h'
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 20, 8);
            $table->json('indicators')->nullable(); // RSI, MACD, BB, etc.
            $table->timestamp('candle_time'); // Timestamp of the candle
            $table->timestamps();

            $table->index(['symbol', 'timeframe', 'candle_time']);
            $table->index('candle_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
