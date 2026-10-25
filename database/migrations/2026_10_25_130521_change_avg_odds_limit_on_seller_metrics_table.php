<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('seller_metrics', function (Blueprint $table) {
            // 12 integer digits + 2 decimal digits = up to 999,999,999,999.99 (~1 trillion)
            $table->decimal('avg_odds', 14, 2)->default(0)->change();
        });
    }

    public function down()
    {
        // Clamp values first so rollback won't fail if any row exceeds the old range
        DB::statement('UPDATE seller_metrics SET avg_odds = LEAST(avg_odds, 999.99)');

        Schema::table('seller_metrics', function (Blueprint $table) {
            $table->decimal('avg_odds', 5, 2)->default(0)->change();
        });
    }
};