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
        Schema::create('contest_entries', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('contest_id')
                ->constrained('contests')
                ->cascadeOnDelete();
        
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
        
            // 'pending' | 'accepted' | 'rejected' | 'disqualified'
            $table->string('status')->default('pending');
        
            $table->unsignedInteger('score_correct')->nullable();
            $table->decimal('score_units', 10, 2)->nullable();
            $table->unsignedInteger('rank_final')->nullable();
        
            $table->timestamp('joined_at');
            $table->timestamp('settled_at')->nullable();
        
            $table->timestamps();
        
            // One entry per user per contest.
            $table->unique(['contest_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_entries');
    }
};
