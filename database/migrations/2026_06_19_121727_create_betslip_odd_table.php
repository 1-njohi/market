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
        Schema::createIfNotExistsIfNotExists('betslip_odd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('betslip_id')->constrained()->onDelete('cascade');
            $table->foreignId('odd_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, won, lost
            $table->decimal('odd_value_at_time', 8, 2);
            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['betslip_id', 'odd_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('betslip_odd');
    }
};
