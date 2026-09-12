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
        Schema::createIfNotExists('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('KES');
            $table->string('payment_method'); // bank, mobile money, etc.
            $table->json('destination')->nullable(); // Bank details, mobile number, etc.
            $table->string('status'); // pending, processing, completed, failed
            $table->json('paystack_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
