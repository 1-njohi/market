<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->boolean('is_matchday')->default(false)->index();
            $table->string('season')->nullable()->index();
            $table->string('league_code')->nullable()->index();
            $table->unsignedInteger('matchday_number')->nullable();
            $table->timestamp('locks_at')->nullable()->index();
        });

        // Plain unique on (season, league_code, matchday_number). Nullable
        // rows will not collide on MySQL/SQLite — non-matchday contests
        // leave all three null and stay unconstrained.
        Schema::table('contests', function (Blueprint $table) {
            $table->unique(
                ['season', 'league_code', 'matchday_number'],
                'contests_matchday_unique'
            );
        });
    }
    public function down(): void
    {
        // SQLite requires indexes to be dropped before the columns they
        // reference. Laravel's $table->dropColumn() on a column that still
        // has an index raises "error in index ... no such column". Drop
        // every index we created first, in its own Schema::table call.
        Schema::table('contests', function (Blueprint $table) {
            $table->dropUnique('contests_matchday_unique');
            $table->dropIndex(['is_matchday']);
            $table->dropIndex(['season']);
            $table->dropIndex(['league_code']);
            $table->dropIndex(['locks_at']);
        });

        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn([
                'is_matchday',
                'season',
                'league_code',
                'matchday_number',
                'locks_at',
            ]);
        });
    }
};