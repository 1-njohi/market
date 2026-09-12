<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createIfNotExists('fixture_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('player_id')->nullable()->constrained('players');
            $table->foreignId('assist_player_id')->nullable()->constrained('players');
            $table->string('type'); // Goal, Card, subst, etc.
            $table->string('detail')->nullable(); // Normal Goal, Own Goal, Yellow Card, etc.
            $table->string('comments')->nullable();
            $table->integer('time_elapsed')->nullable();
            $table->integer('time_extra')->nullable();
            $table->timestamps();

            $table->index('fixture_id');
            $table->index(['player_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_events');
    }
};
