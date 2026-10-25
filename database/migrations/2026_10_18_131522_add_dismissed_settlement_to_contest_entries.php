<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contest_entries', function (Blueprint $table) {
            $table->timestamp('dismissed_settlement_at')->nullable()->after('settled_at');
        });
    }

    public function down(): void
    {
        Schema::table('contest_entries', function (Blueprint $table) {
            $table->dropColumn('dismissed_settlement_at');
        });
    }
};