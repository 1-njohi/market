<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable + unique. Existing users will stay null until
            // they touch their profile; new users get a code via the
            // User model's creating hook.
            //
            // NULLs are distinct in both MySQL and SQLite for unique
            // indexes, so multiple legacy users can coexist with no code.
            $table->string('referral_code')->nullable()->unique()->after('code');

            $table->foreignId('referred_by_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('welcome_discount_used')
                ->default(false)
                ->after('referred_by_id');
        });
    }

    public function down(): void
    {
        // SQLite (and MySQL with strict FK checks) refuses to drop a column
        // that still has a unique index referencing it. Drop the index first.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_id');
            $table->dropColumn(['referral_code', 'welcome_discount_used']);
        });
    }
};