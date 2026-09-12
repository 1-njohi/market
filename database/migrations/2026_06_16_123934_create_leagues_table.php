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

        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id')->default(1);
            $table->integer('id_on_api')->unique();
            $table->string('name');
            $table->unsignedBigInteger('country_id');
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('flag')->nullable();
            $table->integer('season')->nullable();
            $table->integer('priority')->nullable();
            $table->string('round')->nullable();
            $table->boolean('standings')->default(false);
            $table->timestamps();

            $table->index('country_id');
            $table->index('sport_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
