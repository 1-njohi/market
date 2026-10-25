<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // source_id must accommodate both integer IDs (Contest, ContestEntry)
        // and UUID IDs (Betslip). A varchar(36) holds both.
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('source_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->change();
        });
    }
};