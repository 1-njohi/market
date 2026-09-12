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
        Schema::createIfNotExists('player_match_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('team_id')->constrained('teams');

            // Game info
            $table->integer('minutes')->nullable();
            $table->integer('number')->nullable();
            $table->string('position')->nullable(); // G, D, M, F
            $table->decimal('rating', 3, 1)->nullable();
            $table->boolean('captain')->default(false);
            $table->boolean('substitute')->default(false);

            // Goals & shots
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('shots_total')->default(0);
            $table->integer('shots_on_target')->default(0);

            // Passing
            $table->integer('passes_total')->default(0);
            $table->integer('passes_accuracy')->default(0); // % or count

            // Defense
            $table->integer('tackles')->default(0);
            $table->integer('blocks')->default(0);
            $table->integer('interceptions')->default(0);

            // Duels
            $table->integer('duels_total')->default(0);
            $table->integer('duels_won')->default(0);

            // Dribbles
            $table->integer('dribbles_attempts')->default(0);
            $table->integer('dribbles_success')->default(0);

            // Fouls
            $table->integer('fouls_drawn')->default(0);
            $table->integer('fouls_committed')->default(0);

            // Cards
            $table->integer('yellow_cards')->default(0);
            $table->integer('red_cards')->default(0);

            // Penalties
            $table->integer('penalty_scored')->default(0);
            $table->integer('penalty_missed')->default(0);
            $table->integer('penalty_saved')->default(0);

            $table->timestamps();

            $table->index('fixture_id');
            $table->index('player_id');
            $table->index('team_id');
            $table->unique(['fixture_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_match_statistics');
    }
};
