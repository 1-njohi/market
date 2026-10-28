<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_analyst')->default(false)->after('is_admin');
            $table->string('analyst_slug', 64)->nullable()->unique()->after('is_analyst');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['analyst_slug']);
            $table->dropColumn(['is_analyst', 'analyst_slug']);
        });
    }
};