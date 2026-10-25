<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contest_picks', function (Blueprint $table) {
            $table->timestamp('revealed_at')->nullable()->after('points')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contest_picks', function (Blueprint $table) {
            $table->dropIndex(['revealed_at']);
            $table->dropColumn('revealed_at');
        });
    }
};