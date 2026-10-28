<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->boolean('is_analyst_slip')->default(false)->after('is_delisted');

            $table->foreignId('analyst_id')->nullable()->after('is_analyst_slip')
                ->constrained('users')->nullOnDelete();

            $table->text('reasoning')->nullable()->after('caption');
            $table->timestamp('analyst_published_at')->nullable()->after('reasoning');
            $table->boolean('is_edited')->default(false)->after('settled_at');
            $table->unsignedInteger('revision_count')->default(0)->after('is_edited');
            $table->string('analyst_unpublished_reason', 255)->nullable()->after('revision_count');
            $table->text('post_settlement_note')->nullable()->after('analyst_unpublished_reason');
            $table->timestamp('post_settlement_note_at')->nullable()->after('post_settlement_note');

            $table->index(['is_analyst_slip', 'analyst_published_at'], 'betslips_analyst_published_idx');
            $table->index(['analyst_id', 'analyst_published_at'], 'betslips_analyst_id_published_idx');
            $table->index(['is_analyst_slip', 'status'], 'betslips_analyst_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('betslips', function (Blueprint $table) {
            $table->dropForeign(['analyst_id']);
            $table->dropIndex('betslips_analyst_published_idx');
            $table->dropIndex('betslips_analyst_id_published_idx');
            $table->dropIndex('betslips_analyst_status_idx');
            $table->dropColumn([
                'is_analyst_slip', 'analyst_id', 'reasoning',
                'analyst_published_at', 'is_edited', 'revision_count',
                'analyst_unpublished_reason', 'post_settlement_note',
                'post_settlement_note_at',
            ]);
        });
    }
};