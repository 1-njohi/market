<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('betslip_user_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('betslip_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('total_odds', 10, 2);
            $table->string('status')->default('pending'); // pending, completed, refunded
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['betslip_id', 'buyer_id']);
            $table->index(['buyer_id', 'status']);
            $table->unique(['betslip_id', 'buyer_id']); // Prevent duplicate purchases
        });
    }

    public function down()
    {
        Schema::dropIfExists('betslip_user_purchases');
    }
};