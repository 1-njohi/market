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
        Schema::createIfNotExists('odds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id')->index();
            $table->unsignedBigInteger('market_id')->index();
            $table->string('value');
            $table->decimal('odd');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
