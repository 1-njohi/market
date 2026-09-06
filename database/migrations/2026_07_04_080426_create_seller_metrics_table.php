<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seller_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Performance metrics
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('roi', 5, 2)->default(0);
            $table->integer('total_sold')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('avg_price', 10, 2)->default(0);
            $table->decimal('avg_odds', 5, 2)->default(0);
            $table->decimal('avg_legs', 3, 1)->default(0);
            
            // Follower metrics
            $table->integer('follower_count')->default(0);
            $table->integer('profile_views')->default(0);
            
            // Daily/weekly performance
            $table->json('weekly_performance')->nullable();
            $table->json('monthly_performance')->nullable();
            
            // Calculated at
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('calculated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_metrics');
    }
};