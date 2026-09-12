<?php

namespace App\Http\Controllers;

use App\Services\MpesaWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MpesaWithdrawalCallbackController extends Controller
{
    public function __construct(
        protected MpesaWithdrawalService $service,
    ) {}

    /**
     * Safaricom sends the final result here (POST).
     */
    public function result(Request $request): JsonResponse
    {
        Log::info("B2C result callback", $request->all());

        try {
            $this->service->handleResult($request->all());
        } catch (\Throwable $e) {
            Log::error("B2C result handler threw", [
                'error' => $e->getMessage(),
            ]);
        }

        // Always ack Safaricom, regardless of processing outcome
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Safaricom sends this when the request times out on their side.
     */
    public function timeout(Request $request): JsonResponse
    {
        Log::warning("B2C timeout callback", $request->all());

        try {
            $this->service->handleTimeout($request->all());
        } catch (\Throwable $e) {
            Log::error("B2C timeout handler threw", [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}