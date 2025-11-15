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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('symbol'); // e.g., 'BTCUSDT'
            $table->enum('side', ['LONG', 'SHORT']);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->decimal('quantity', 20, 8);
            $table->integer('leverage')->default(1);
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->enum('status', ['OPEN', 'CLOSED', 'CANCELLED'])->default('OPEN');
            $table->decimal('pnl', 20, 8)->nullable(); // Profit/Loss in USDT
            $table->decimal('pnl_percentage', 10, 4)->nullable();
            $table->string('binance_order_id')->nullable();
            $table->unsignedBigInteger('ai_decision_id')->nullable(); // Soft reference to ai_decisions
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'status']);
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
