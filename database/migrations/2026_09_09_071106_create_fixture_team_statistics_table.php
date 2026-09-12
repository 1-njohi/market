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
        Schema::createIfNotExists('fixture_team_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams');
            $table->json('statistics')->nullable(); // { "Shots on Goal": 2, "Ball Possession": "56%", ... }
            $table->timestamps();

            $table->index('fixture_id');
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_team_statistics');
    }
};
