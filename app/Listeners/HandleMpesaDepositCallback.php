<?php

namespace App\Listeners;

use App\Services\MpesaDepositService;
use FelixMuhoro\Mpesa\Events\PaymentFailed;
use FelixMuhoro\Mpesa\Events\PaymentSuccessful;
use Illuminate\Support\Facades\Log;

class HandleMpesaDepositCallback
{
    public function __construct(
        protected MpesaDepositService $deposits,
    ) {
    }

    public function onSuccess(PaymentSuccessful $event): void
    {
        try {
            $this->deposits->handleCallback($event->payload);
        } catch (\Throwable $e) {
            Log::error('Deposit success handler threw', ['error' => $e->getMessage()]);
        }
    }

    public function onFailure(PaymentFailed $event): void
    {
        try {
            $this->deposits->handleCallback($event->payload);
        } catch (\Throwable $e) {
            Log::error('Deposit failure handler threw', ['error' => $e->getMessage()]);
        }
    }
}