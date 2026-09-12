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
        Schema::createIfNotExists('fixtures', function (Blueprint $table) {
            $table->id();
            $table->integer('id_on_api')->unique();
            $table->string('referee')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('date')->nullable();
            $table->integer('timestamp')->nullable();
            $table->integer('period_first')->nullable();
            $table->integer('period_second')->nullable();

            // Foreign keys
            $table->foreignId('venue_id')->nullable()->constrained();
            $table->foreignId('league_id')->nullable()->constrained();
            $table->foreignId('home_team_id')->nullable(); //->constrained('teams');
            $table->foreignId('away_team_id')->nullable(); //->constrained('teams');

            // Status
            $table->string('status_long')->nullable();
            $table->string('status_short')->nullable();
            $table->integer('status_elapsed')->nullable();
            $table->integer('status_extra')->nullable();

            // Goals
            $table->integer('goals_home')->nullable();
            $table->integer('goals_away')->nullable();

            // Scores
            $table->integer('halftime_home')->nullable();
            $table->integer('halftime_away')->nullable();
            $table->integer('fulltime_home')->nullable();
            $table->integer('fulltime_away')->nullable();
            $table->integer('extratime_home')->nullable();
            $table->integer('extratime_away')->nullable();
            $table->integer('penalty_home')->nullable();
            $table->integer('penalty_away')->nullable();

            // Derived
            $table->boolean('home_winner')->default(false);
            $table->boolean('settled')->default(false);

            $table->timestamps();

            $table->index('id_on_api');
            $table->index('league_id');
            $table->index(['home_team_id', 'away_team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
