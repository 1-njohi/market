<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('referrer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // A referee can only be attributed once, ever.
            $table->foreignId('referee_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Canonical form of the code used (uppercase). Audit trail
            // even if the referrer changes their code later.
            $table->string('code_used');

            $table->timestamp('signed_up_at');
            $table->timestamp('expires_at');

            $table->unsignedInteger('wins_counted')->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);

            $table->timestamps();

            $table->index('referrer_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};