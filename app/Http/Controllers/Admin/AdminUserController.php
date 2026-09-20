<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
    ) {
    }

    public function index(Request $request)
    {
        $query = User::query()->with('wallet');

        // ── Search ──
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // ── Role filter ──
        if ($role = $request->input('role')) {
            if ($role === 'seller') {
                $query->whereHas('betslips');
            } elseif ($role === 'buyer') {
                $query->whereHas('purchases');
            } elseif ($role === 'both') {
                $query->whereHas('betslips')->whereHas('purchases');
            }
        }

        // ── Status filter ──
        if ($status = $request->input('status')) {
            match ($status) {
                'suspended' => $query->whereNotNull('suspended_at'),
                'active' => $query->whereNull('suspended_at'),
                'unverified' => $query->whereNull('email_verified_at'),
                'admin' => $query->where('is_admin', true),
                default => null,
            };
        }

        // ── Sort ──
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name' => $query->orderBy('name', 'asc'),
            'balance' => $query->orderByDesc(
                Wallet::select('balance')->whereColumn('wallets.user_id', 'users.id')
            ),
            default => $query->orderByDesc('created_at'),
        };

        $users = $query->paginate(20)->withQueryString();
        $users->through(fn(User $user) => tap($user)->append('joined_ago'));

        // ── Summary counts (independent of current filters) ──
        $counts = [
            'total' => User::count(),
            'sellers' => User::whereHas('betslips')->count(),
            'buyers' => User::whereHas('purchases')->count(),
            'suspended' => User::whereNotNull('suspended_at')->count(),
        ];

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'counts' => $counts,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'status' => $request->input('status', ''),
                'sort' => $sort,
            ],
        ]);
    }
    public function show($id)
    {
        $user = User::with([
            'wallet',
            'sellerMetric',
            'betslips' => fn($q) => $q->latest()->limit(10),
            'purchases' => fn($q) => $q->with('betslip')->latest()->limit(10),
            'transactions' => fn($q) => $q->latest()->limit(15),
        ])->findOrFail($id);

        return Inertia::render('admin/users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'code' => $user->code,
                'country_code' => $user->country_code,
                'avatar' => $user->profile_picture_url
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                'is_admin' => $user->is_admin,
                'is_verified' => !is_null($user->email_verified_at),
                'suspended_at' => $user->suspended_at?->toISOString(),
                'suspension_reason' => $user->suspension_reason,
                'joined_at' => $user->created_at->toISOString(),
                'joined_ago' => $user->created_at->diffForHumans(),
            ],

            'wallet' => [
                'balance' => (float) ($user->wallet->balance ?? 0),
                'escrow_balance' => (float) ($user->wallet->escrow_balance ?? 0),
                'total_deposited' => (float) ($user->wallet->total_deposited ?? 0),
                'total_withdrawn' => (float) ($user->wallet->total_withdrawn ?? 0),
                'currency' => $user->wallet->currency ?? 'KES',
            ],

            'stats' => [
                'betslips_created' => $user->betslips()->count(),
                'betslips_purchased' => $user->purchases()->count(),
                'followers' => $user->followers()->count(),
                'following' => $user->following()->count(),
            ],

            'seller_metric' => $user->sellerMetric ? [
                'win_rate' => (float) $user->sellerMetric->win_rate,
                'roi' => (float) $user->sellerMetric->roi,
                'total_sold' => (int) $user->sellerMetric->total_sold,
                'total_revenue' => (float) $user->sellerMetric->total_revenue,
            ] : null,

            'recent_betslips' => $user->betslips->map(fn($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'status' => $b->status,
                'is_winner' => (bool) $b->is_winner,
                'total_odds' => (float) $b->total_odds,
                'price' => (float) $b->price,
                'created_at' => $b->created_at->toISOString(),
                'created_ago' => $b->created_at->diffForHumans(),
            ]),

            'recent_purchases' => $user->purchases->map(fn($p) => [
                'id' => $p->id,
                'betslip_code' => $p->betslip->code ?? 'N/A',
                'status' => $p->status,
                'purchase_price' => (float) $p->purchase_price,
                'created_at' => $p->created_at->toISOString(),
                'created_ago' => $p->created_at->diffForHumans(),
            ]),

            'recent_transactions' => $user->transactions->map(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'balance_after' => (float) $t->balance_after,
                'description' => $t->description,
                'reference' => $t->reference,
                'created_at' => $t->created_at->toISOString(),
                'created_ago' => $t->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function suspend(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot suspend your own account.');
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Cannot suspend the last admin account.');
        }

        $user->update([
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'],
        ]);

        Log::warning('User suspended by admin', [
            'user_id' => $user->id,
            'admin_id' => Auth::id(),
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', "{$user->name} has been suspended.");
    }

    public function unsuspend($id)
    {
        $user = User::findOrFail($id);

        if (!$user->isSuspended()) {
            return back()->with('error', 'User is not suspended.');
        }

        $user->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        Log::info('User unsuspended by admin', [
            'user_id' => $user->id,
            'admin_id' => Auth::id(),
        ]);

        return back()->with('success', "{$user->name} has been reactivated.");
    }

    public function adjustBalance(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $validated['amount'];
        $reference = 'ADMIN-' . now()->timestamp . '-' . Auth::id();

        try {
            if ($amount > 0) {
                $this->walletService->credit(
                    $user,
                    $amount,
                    'admin_credit',
                    $reference,
                    'Admin credit by ' . Auth::user()->name . ': ' . $validated['reason']
                );
            } else {
                $this->walletService->debit(
                    $user,
                    abs($amount),
                    'admin_debit',
                    $reference,
                    'Admin debit by ' . Auth::user()->name . ': ' . $validated['reason']
                );
            }

            Log::warning('Balance adjusted by admin', [
                'user_id' => $user->id,
                'admin_id' => Auth::id(),
                'amount' => $amount,
                'reason' => $validated['reason'],
                'reference' => $reference,
            ]);

            $verb = $amount > 0 ? 'credited to' : 'debited from';
            return back()->with(
                'success',
                'KES ' . number_format(abs($amount), 2) . " {$verb} {$user->name}'s wallet."
            );

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}