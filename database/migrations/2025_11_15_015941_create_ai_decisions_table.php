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
        Schema::create('ai_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('symbol'); // e.g., 'BTCUSDT'
            $table->json('timeframes_analyzed'); // ['5m', '15m', '30m', '1h']
            $table->json('market_conditions'); // Analysis data from AI
            $table->enum('decision', ['BUY', 'SELL', 'HOLD', 'CLOSE']);
            $table->decimal('confidence', 5, 2); // 0-100
            $table->text('reasoning'); // AI's explanation
            $table->json('risk_assessment')->nullable();
            $table->integer('recommended_leverage')->nullable();
            $table->decimal('recommended_stop_loss', 20, 8)->nullable();
            $table->decimal('recommended_take_profit', 20, 8)->nullable();
            $table->boolean('executed')->default(false);
            $table->text('execution_error')->nullable();
            $table->timestamp('analyzed_at');
            $table->timestamps();

            $table->index(['symbol', 'analyzed_at']);
            $table->index('executed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_decisions');
    }
};
