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

        return Inertia::render('Wallet/Transactions', [
            'transactions' => $transactions,
            'filters' => ['type' => $type],
        ]);
    }
}