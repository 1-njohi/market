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
        Schema::create('contest_legs', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('contest_id')
                ->constrained('contests')
                ->cascadeOnDelete();
        
            $table->foreignId('fixture_id')
                ->constrained('fixtures')
                ->cascadeOnDelete();
        
            $table->foreignId('market_id')
                ->constrained('markets')
                ->cascadeOnDelete();
        
            // 'pending' | 'won' | 'lost' | 'void'
            $table->string('status')->default('pending');
        
            // The actual outcome once known ('Home', 'Over 2.5', etc.)
            $table->string('result_selection')->nullable();
        
            $table->timestamp('resolved_at')->nullable();
        
            $table->timestamps();
        
            // A contest can't have the same fixture+market twice.
            $table->unique(['contest_id', 'fixture_id', 'market_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_legs');
    }
};
