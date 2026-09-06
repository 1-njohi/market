<?php

namespace App\Http\Controllers;

use App\Services\PaystackDepositService;
use App\Services\PaystackWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaystackController extends Controller
{
    protected PaystackDepositService $depositService;
    protected PaystackWithdrawalService $withdrawalService;

    public function __construct(
        PaystackDepositService $depositService,
        PaystackWithdrawalService $withdrawalService
    ) {
        $this->depositService = $depositService;
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Initiate a deposit
     */
    public function initiateDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $user = Auth::user();
        $result = $this->depositService->initiateDeposit(
            $user,
            $request->amount,
            $user->email
        );

        if ($request->wantsJson()) {
            // Return JSON for AJAX requests
            return response()->json($result);
        }

        // For regular form submissions, redirect as before
        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect($result['authorization_url']);
    }

    /**
     * Handle Paystack callback
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect('/seller/dashboard')->with('error', 'Invalid reference');
        }

        $result = $this->depositService->verifyTransaction($reference);

        if ($result['success']) {
            return redirect('/seller/dashboard')->with('success', 'Deposit successful!');
        }

        return redirect('/seller/dashboard')->with('error', 'Deposit verification failed');
    }

    /**
     * Initiate a withdrawal
     */
    public function initiateWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $user = Auth::user();

        $result = $this->withdrawalService->initiateWithdrawal($user, $request->amount, [
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'bank_code' => $request->bank_code,
        ]);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Handle Paystack webhook
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature (implement for security)
        // $this->verifyWebhookSignature($request);

        $payload = $request->all();

        // Process the webhook based on event type
        if (isset($payload['event'])) {
            if (str_starts_with($payload['event'], 'charge.')) {
                $this->depositService->handleWebhook($payload);
            } elseif (str_starts_with($payload['event'], 'transfer.')) {
                $this->withdrawalService->handleWebhook($payload);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Verify Paystack webhook signature (optional)
     */
    protected function verifyWebhookSignature(Request $request)
    {
        // Implement Paystack signature verification
        // https://paystack.com/docs/payments/webhooks#signature-verification
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('paystack.secret_key');

        $computedSignature = hash_hmac('sha512', $payload, $secret);

        if ($signature !== $computedSignature) {
            abort(401, 'Invalid webhook signature');
        }
    }
}
