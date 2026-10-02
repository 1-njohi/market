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
        Schema::create('contest_picks', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('contest_entry_id')
                ->constrained('contest_entries')
                ->cascadeOnDelete();
        
            $table->foreignId('contest_leg_id')
                ->constrained('contest_legs')
                ->cascadeOnDelete();
        
            $table->string('selection');
        
            // Snapshot of odds when the pick was made. Never re-read.
            $table->decimal('odds_at_pick', 10, 2);
        
            // 'pending' | 'correct' | 'incorrect' | 'void'
            $table->string('status')->default('pending');
        
            $table->decimal('points', 10, 2)->nullable();
        
            $table->timestamps();
        
            // One pick per leg per entry.
            $table->unique(['contest_entry_id', 'contest_leg_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_picks');
    }
};
