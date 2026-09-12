<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'betslips_user_status_idx');
            $table->index(['status', 'updated_at'], 'betslips_status_updated_idx');
        });

        Schema::table('seller_metrics', function (Blueprint $table) {
            $table->index(['roi', 'win_rate'], 'seller_metrics_roi_wr_idx');
        });
    }

    public function down(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->dropIndex('betslips_user_status_idx');
            $table->dropIndex('betslips_status_updated_idx');
        });

        Schema::table('seller_metrics', function (Blueprint $table) {
            $table->dropIndex('seller_metrics_roi_wr_idx');
        });
    }
};
