<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contest_legs', function (Blueprint $table) {
            $table->dropColumn('result_selection');
            $table->json('result_selections')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('contest_legs', function (Blueprint $table) {
            $table->dropColumn('result_selections');
            $table->string('result_selection')->nullable()->after('status');
        });
    }
};