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
        Schema::create('fixture_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id')->unique()->index();
            $table->string('long');
            $table->string('short');
            $table->integer('elapsed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_statuses');
    }
};
