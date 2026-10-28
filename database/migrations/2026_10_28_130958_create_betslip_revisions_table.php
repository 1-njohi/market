<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('betslip_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('betslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users')->restrictOnDelete();

            $table->string('caption', 280)->nullable();
            $table->text('reasoning')->nullable();
            $table->json('legs');

            $table->string('edit_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['betslip_id', 'created_at'], 'betslip_revisions_betslip_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('betslip_revisions');
    }
};