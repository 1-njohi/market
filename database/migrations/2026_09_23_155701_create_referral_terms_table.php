<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referral_terms', function (Blueprint $table) {
            $table->id();

            // One override per referrer. Absence of a row = defaults.
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('reward_percentage', 5, 4);
            $table->unsignedInteger('max_transactions');
            $table->unsignedInteger('window_months');
            $table->decimal('referee_discount_pct', 5, 4);
            $table->decimal('referee_discount_cap', 10, 2);

            $table->text('notes')->nullable();

            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_terms');
    }
};