<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every metric filters betslips by created_at.
        Schema::table('betslips', function (Blueprint $table) {
            $table->index('created_at', 'betslips_created_at_index');
        });

        // Buyer repeat rate and time-to-first-sale filter by purchased_at.
        Schema::table('betslip_user_purchases', function (Blueprint $table) {
            $table->index('purchased_at', 'betslip_user_purchases_purchased_at_index');
        });

        // Seller activation filters users by created_at (signup cohort).
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'users_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->dropIndex('betslips_created_at_index');
        });

        Schema::table('betslip_user_purchases', function (Blueprint $table) {
            $table->dropIndex('betslip_user_purchases_purchased_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_created_at_index');
        });
    }
};
