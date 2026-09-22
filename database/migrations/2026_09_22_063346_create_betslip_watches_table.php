<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('betslip_watches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // betslips.id is a UUID (TraitUuid on the model) — column must match.
            $table->foreignUuid('betslip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('watched_at');
            $table->timestamps();

            // A user watches a given betslip at most once.
            $table->unique(['user_id', 'betslip_id']);

            // "My watchlist, newest first."
            $table->index(['user_id', 'watched_at']);

            // "How many people are watching this betslip."
            $table->index('betslip_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('betslip_watches');
    }
};