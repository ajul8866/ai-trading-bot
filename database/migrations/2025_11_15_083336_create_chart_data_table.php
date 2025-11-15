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
        Schema::create('chart_data', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('timeframe', 10);
            $table->timestamp('timestamp');
            $table->decimal('open', 16, 8);
            $table->decimal('high', 16, 8);
            $table->decimal('low', 16, 8);
            $table->decimal('close', 16, 8);
            $table->decimal('volume', 20, 8);
            $table->timestamps();

            // Indexes for performance
            $table->index(['symbol', 'timeframe', 'timestamp']);
            $table->unique(['symbol', 'timeframe', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_data');
    }
};
