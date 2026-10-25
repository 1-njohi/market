<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_leagues', function (Blueprint $table) {
            $table->id();
            $table->string('season')->index();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('invite_code', 12)->unique();
            $table->unsignedInteger('max_members');
            $table->timestamps();

            $table->unique(['season', 'name'], 'sub_leagues_season_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_leagues');
    }
};