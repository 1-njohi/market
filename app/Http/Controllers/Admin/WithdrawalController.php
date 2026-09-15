<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\MpesaWithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = Withdrawal::with('user:id,name,email,code');

        if ($status = $request->get('status', 'pending')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                                                       ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $withdrawals = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($w) => [
                'id' => $w->id,
                'reference' => $w->reference,
                'amount' => (float) $w->amount,
                'currency' => $w->currency,
                'status' => $w->status,
                'destination' => $w->destination,
                'user' => $w->user ? [
                    'id' => $w->user->id,
                    'name' => $w->user->name,
                    'code' => $w->user->code,
                ] : null,
                'mpesa_receipt' => $w->mpesa_receipt,
                'failure_reason' => $w->failure_reason,
                'created_at' => $w->created_at->toISOString(),
                'completed_at' => $w->completed_at?->toISOString(),
            ]);

        $counts = [
            'pending' => Withdrawal::where('status', 'pending')->count(),
            'processing' => Withdrawal::where('status', 'processing')->count(),
            'completed_today' => Withdrawal::where('status', 'completed')->whereDate('completed_at', today())->count(),
            'failed_today' => Withdrawal::where('status', 'failed')->whereDate('updated_at', today())->count(),
        ];

        return Inertia::render('admin/withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'counts' => $counts,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Approve a pending withdrawal → moves it to processing and fires M-Pesa B2C.
     * Wire this to your existing MpesaWithdrawalService flow. For now, mark processing.
     */
    public function approve(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be approved.');
        }

        $withdrawal->update(['status' => 'processing']);

        Log::info('Withdrawal approved', [
            'admin_id' => auth()->id(),
            'withdrawal_id' => $withdrawal->id,
        ]);

        // TODO: dispatch B2C request
        // app(MpesaWithdrawalService::class)->dispatch($withdrawal);

        return back()->with('success', "Withdrawal {$withdrawal->reference} approved.");
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        if (!in_array($withdrawal->status, ['pending', 'processing'])) {
            return back()->with('error', 'Withdrawal cannot be rejected in its current state.');
        }

        // Refund the user's wallet
        \DB::transaction(function () use ($withdrawal, $validated) {
            app(\App\Services\WalletService::class)->credit(
                $withdrawal->user,
                (float) $withdrawal->amount,
                'refund',
                $withdrawal->reference . '-reject',
                "Withdrawal rejected by admin: {$validated['reason']}"
            );

            $withdrawal->update([
                'status' => 'failed',
                'failure_reason' => $validated['reason'],
            ]);
        });

        Log::warning('Withdrawal rejected', [
            'admin_id' => auth()->id(),
            'withdrawal_id' => $withdrawal->id,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Withdrawal rejected and refunded.');
    }
}