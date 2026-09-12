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
        Schema::createIfNotExistsIfNotExists('teams', function (Blueprint $table) {
            $table->id();
            $table->integer('id_on_api')->unique();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->json('colors')->nullable(); // { player: { primary, number, border }, goalkeeper: {...} }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
