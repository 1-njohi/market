<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createIfNotExists('betslips', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('code')->unique();
            $table->decimal('total_odds');
            $table->decimal('price');
            $table->string('status')->default('pending');
            $table->integer('priority_score')->default(1);
            $table->longText('caption')->nullable();
            $table->integer('remaining')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('betslips');
    }
};
