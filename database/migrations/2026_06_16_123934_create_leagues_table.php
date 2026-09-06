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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id')->default(1);
            $table->integer('id_on_api');
            $table->unsignedBigInteger('country_id');
            $table->string('name');
            $table->string('type');
            $table->string('logo');
            $table->integer('priority')->default(1);
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
