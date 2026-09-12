<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('mpesa_conversation_id')->nullable()->after('reference');
            $table->string('mpesa_originator_conversation_id')->nullable()->after('mpesa_conversation_id');
            $table->string('mpesa_receipt')->nullable()->after('mpesa_originator_conversation_id');
            $table->json('mpesa_response')->nullable()->after('paystack_response');
            $table->string('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'mpesa_conversation_id',
                'mpesa_originator_conversation_id',
                'mpesa_receipt',
                'mpesa_response',
                'failure_reason',
            ]);
        });
    }
};