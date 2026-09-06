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
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_notifications_enabled')->default(true);
            $table->integer('relationship_score')->default(1);
            $table->timestamp('followed_at')->useCurrent();
            $table->timestamps();

            // Prevent duplicate follows
            $table->unique(['follower_id', 'following_id']);

            // Indexes for performance
            $table->index(['follower_id', 'following_id']);
            $table->index('following_id');
            $table->index('followed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
