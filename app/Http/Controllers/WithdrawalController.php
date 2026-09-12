<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MpesaWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    public function __construct(
        protected MpesaWithdrawalService $service,
    ) {}

    /**
     * POST /withdrawals
     * Body: { amount: 500, phone: "2547XXXXXXXX" }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'phone'  => 'required|string|min:9|max:13',
        ]);

        try {
            $withdrawal = $this->service->initiate(
                // Auth::user(),
                User::first(),
                (float) $validated['amount'],
                $validated['phone'],
            );

            return response()->json([
                'success'   => true,
                'message'   => 'Withdrawal initiated. Check your phone for the M-Pesa prompt.',
                'reference' => $withdrawal->reference,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}