<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            // When true, join requests are accepted immediately — the host
            // has no approval step. Only meaningful while the contest is
            // still `open`; the deadline is authoritative regardless.
            $table->boolean('auto_entry')->default(false)->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn('auto_entry');
        });
    }
};