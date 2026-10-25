<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every betslip carries a price so ROI math works for all users.
        // Ineligible sellers' slips get the sentinel value 1 — they're
        // never sellable (is_sellable = false) so the price never leaves
        // the platform, but it feeds into the seller's aggregate stats.
        //
        // Existing rows: default backfills on the ALTER in most drivers.
        // The WHERE clause is a defensive no-op for environments that
        // don't auto-fill.
        Schema::table('betslips', function (Blueprint $table) {
            $table->decimal('price')->default(1)->change();
        });

        \DB::table('betslips')->whereNull('price')->update(['price' => 1]);
    }

    public function down(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->decimal('price')->default(null)->change();
        });
    }
};