<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_leagues', function (Blueprint $table) {
            $table->json('league_codes')->nullable()->after('season');
        });
    }

    public function down(): void
    {
        Schema::table('sub_leagues', function (Blueprint $table) {
            $table->dropColumn('league_codes');
        });
    }
};