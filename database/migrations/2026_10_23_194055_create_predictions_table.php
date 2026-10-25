<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();

            // Snapshot of the pick at the moment the prediction was made.
            $table->string('selection');
            $table->decimal('odds_at_pick', 8, 2);

            // 1 (wild guess) to 5 (locked in). Always required.
            $table->unsignedTinyInteger('confidence');

            // Where the prediction came from. Nullable source_id lets us
            // create predictions without tying them to a specific object
            // (e.g. a "quick pick" widget).
            $table->string('source_type');   // 'betslip' | 'jackpot' | 'matchday' | 'quick_pick'
            $table->unsignedBigInteger('source_id')->nullable();

            // Scoring state.
            $table->string('status')->default('pending'); // pending | won | lost | void
            $table->integer('points_impact')->nullable(); // filled at settlement
            $table->timestamp('scored_at')->nullable();

            $table->timestamps();

            // One prediction per user per (fixture, market). This is the
            // deduplication rule that keeps the crowd wisdom clean.
            $table->unique(['user_id', 'fixture_id', 'market_id'], 'predictions_user_fixture_market_unique');

            // Fast lookups for the aggregation.
            $table->index(['fixture_id', 'market_id']);
            $table->index(['status', 'fixture_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};