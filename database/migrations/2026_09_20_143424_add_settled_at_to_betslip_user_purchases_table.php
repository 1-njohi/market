<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('betslip_user_purchases', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('purchased_at');
        });
    }

    public function down(): void
    {
        Schema::table('betslip_user_purchases', function (Blueprint $table) {
            $table->dropColumn('settled_at');
        });
    }
};