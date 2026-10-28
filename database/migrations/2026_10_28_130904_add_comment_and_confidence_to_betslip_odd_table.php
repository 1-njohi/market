<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('betslip_odd', function (Blueprint $table) {
            $table->string('comment', 200)->nullable()->after('odd_value_at_time');
            $table->unsignedTinyInteger('confidence')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('betslip_odd', function (Blueprint $table) {
            $table->dropColumn(['comment', 'confidence']);
        });
    }
};