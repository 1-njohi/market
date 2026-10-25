<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->string('type')->default('standard')->after('visibility')->index();
        });

        DB::table('contests')->where('is_matchday', true)->update(['type' => 'matchday']);
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};