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
        Schema::create('fixture_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id')->unique()->index();
            $table->integer('halftime_home')->nullable();
            $table->integer('halftime_away')->nullable();
            $table->integer('fulltime_home')->nullable();
            $table->integer('fulltime_away')->nullable();
            $table->integer('extratime_home')->nullable();
            $table->integer('extratime_away')->nullable();
            $table->integer('penalty_home')->nullable();
            $table->integer('penalty_away')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_scores');
    }
};
