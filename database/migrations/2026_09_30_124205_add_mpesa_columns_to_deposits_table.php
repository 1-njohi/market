<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('mpesa_checkout_request_id')
                ->nullable()
                ->after('reference')
                ->index();

            $table->string('mpesa_receipt')
                ->nullable()
                ->after('mpesa_checkout_request_id');

            $table->json('mpesa_response')
                ->nullable()
                ->after('paystack_response');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex(['mpesa_checkout_request_id']);
            $table->dropColumn([
                'mpesa_checkout_request_id',
                'mpesa_receipt',
                'mpesa_response',
            ]);
        });
    }
};