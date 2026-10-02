<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->id();

            // Public share code. Non-enumerable, human-readable.
            // Not the primary key — that stays a bigint for FK efficiency.
            $table->string('uuid')->unique();

            $table->foreignId('host_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // 'public' | 'private'. Only 'private' is implemented in v1.
            $table->string('visibility')->default('private');

            // 'open' | 'locked' | 'settled' | 'cancelled'
            $table->string('status')->default('open');

            $table->timestamp('entry_deadline_at');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('settled_at')->nullable();

            $table->timestamps();

            $table->index('host_id');
            $table->index('status');
            $table->index('entry_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contests');
    }
};