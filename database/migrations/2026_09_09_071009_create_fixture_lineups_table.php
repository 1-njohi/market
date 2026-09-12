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
        Schema::createIfNotExistsIfNotExists('fixture_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('coach_id')->nullable()->constrained('coaches');
            $table->string('formation')->nullable();
            $table->json('startXI')->nullable(); // Array of { player_id, number, pos, grid }
            $table->json('substitutes')->nullable(); // Array of { player_id, number, pos, grid }
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
        Schema::dropIfExists('fixture_lineups');
    }
};
