<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * rank_final  → rank_current   (updated on every scoring pass)
     * add           rank_previous  (the value rank_current held before
     *                               the most recent pass; null on the
     *                               first pass, so the UI can show "—")
     *
     * Two Schema::table calls: SQLite requires the rename to be its own
     * statement before adding new columns.
     */
    public function up(): void
    {
        Schema::table('contest_entries', function (Blueprint $table) {
            $table->renameColumn('rank_final', 'rank_current');
        });

        Schema::table('contest_entries', function (Blueprint $table) {
            $table->integer('rank_previous')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contest_entries', function (Blueprint $table) {
            $table->dropColumn('rank_previous');
        });

        Schema::table('contest_entries', function (Blueprint $table) {
            $table->renameColumn('rank_current', 'rank_final');
        });
    }
};