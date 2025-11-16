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
            // Add foreign key constraint for ai_decision_id
            $table->foreign('ai_decision_id')
                  ->references('id')
                  ->on('ai_decisions')
                  ->nullOnDelete(); // Set to null if AI decision is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropForeign(['ai_decision_id']);
        });
    }
};
