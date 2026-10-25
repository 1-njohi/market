<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            // price / (total_odds - 1). The stake a buyer needs to make
            // at an external bookmaker to earn back the price they paid
            // for the slip. Null when total_odds <= 1.0 — should never
            // happen with real odds, but a guarded column is cheaper
            // than a crashing calculation.
            $table->decimal('break_even_stake', 12, 2)
                ->nullable()
                ->after('total_odds');

            // Composite because the marketplace query is always
            //   WHERE status = 'pending' AND break_even_stake <= ?
            // A bare break_even_stake index helps, but the composite
            // lets the engine narrow on the low-cardinality column
            // first and range-scan the second.
            $table->index(['status', 'break_even_stake']);
        });
    }

    public function down(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->dropIndex(['status', 'break_even_stake']);
            $table->dropColumn('break_even_stake');
        });
    }
};