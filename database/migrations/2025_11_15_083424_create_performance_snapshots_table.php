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
        Schema::create('performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('snapshot_at');
            $table->enum('period', ['hourly', 'daily'])->default('hourly');
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->decimal('total_pnl', 16, 8)->default(0);
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('sortino_ratio', 10, 4)->nullable();
            $table->decimal('max_drawdown', 10, 4)->nullable();
            $table->decimal('win_rate', 5, 2)->nullable();
            $table->decimal('avg_win', 16, 8)->nullable();
            $table->decimal('avg_loss', 16, 8)->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['snapshot_at', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_snapshots');
    }
};
