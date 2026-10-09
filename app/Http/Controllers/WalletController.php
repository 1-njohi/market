<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    /**
     * Full paginated transaction list for the authenticated user.
     * Shared by the buyer and seller dashboards — the wallet is per-user,
     * not per-role.
     */
    public function transactions(Request $request)
    {
        $allowedTypes = [
            'deposit', 'withdrawal', 'purchase', 'refund',
            'payout', 'fee', 'pending_release',
        ];

        $type = $request->query('type');
        if (!in_array($type, $allowedTypes, true)) {
            $type = null;
        }

        $query = Transaction::where('user_id', $request->user()->id)
            ->where('balance_type', 'available')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Reshape each row to the same contract the dashboard's
        // recent_transactions uses (all numeric fields as actual floats).
        // Eloquent's decimal cast returns strings, and the frontend calls
        // .toFixed() on amount — so we normalize here, once, at the boundary.
        $transactions->through(function (Transaction $tx) {
            return [
                'id'             => $tx->id,
                'type'           => $tx->type,
                'amount'         => (float) $tx->amount,
                'balance_before' => (float) $tx->balance_before,
                'balance_after'  => (float) $tx->balance_after,
                'description'    => $tx->description,
                'status'         => $tx->status,
                'outcome'        => null, // the pivot-linked outcome isn't loaded here
                'created_at'     => $tx->created_at->toIso8601String(),
            ];
        });

        return Inertia::render('Wallet/Transactions', [
            'transactions' => $transactions,
            'filters' => ['type' => $type],
        ]);
    }
}