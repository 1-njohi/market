<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odds', function (Blueprint $table) {
            $table->unique(
                ['fixture_id', 'market_id', 'value'],
                'odds_fixture_market_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('odds', function (Blueprint $table) {
            $table->dropUnique('odds_fixture_market_value_unique');
        });
    }
};