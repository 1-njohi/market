<?php

namespace App\Http\Controllers;

use App\Services\MpesaDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MpesaDepositController extends Controller
{
    public function __construct(
        protected MpesaDepositService $deposits,
    ) {
    }

    /**
     * POST /deposit/mpesa/initiate
     *
     * Fires an STK Push prompt to the user's phone. The wallet is credited
     * asynchronously once Safaricom's callback confirms the payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'phone' => 'required|string|min:9|max:13',
        ]);

        $result = $this->deposits->initiateDeposit(
            Auth::user(),
            (float) $validated['amount'],
            $validated['phone'],
        );

        return response()->json($result, $result['success'] ? 200 : 200);
    }
}