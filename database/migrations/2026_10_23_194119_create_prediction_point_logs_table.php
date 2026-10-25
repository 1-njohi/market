<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prediction_point_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Positive for gains, negative for losses.
            $table->integer('amount');

            // Snapshot of balance after this change, so the ledger is
            // replayable even if the user row is later modified.
            $table->integer('balance_after');

            // Type categorizes the log line for UI grouping and filters.
            $table->string('type');

            // Human-readable description, e.g. "Won Pick: Arsenal vs Chelsea (Home)"
            $table->string('description');

            // Polymorphic link to what caused the change: a Prediction,
            // a Betslip, an admin adjustment, etc.
            $table->nullableMorphs('source');

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_point_logs');
    }
};