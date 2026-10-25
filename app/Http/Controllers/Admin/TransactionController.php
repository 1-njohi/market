<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    protected int $platformUserId = 1;

    protected array $groups = [
        'deposits' => [
            'label' => 'Deposits',
            'types' => ['deposit'],
        ],
        'withdrawals' => [
            'label' => 'Withdrawals',
            'types' => ['withdrawal'],
        ],
        'trading' => [
            'label' => 'Trading',
            'types' => ['betslip_purchase', 'betslip_sale', 'sale', 'purchase', 'refund', 'payout'],
        ],
        'fees' => [
            'label' => 'Platform Fees',
            'types' => ['fee', 'platform_fee'],
            'requires_user_id' => 1,
        ],
        'seller_fees' => [
            'label' => 'Seller Fees',
            'types' => ['fee', 'platform_fee'],
            // We want the seller's side (not the platform), and only the debits
            'exclude_user_id' => 1,
            'requires_negative_amount' => true,
        ],
        'admin' => [
            'label' => 'Admin Adjustments',
            'types' => ['admin_credit', 'admin_debit'],
        ],
    ];

    protected array $typeLabels = [
        'deposit' => 'Deposit',
        'withdrawal' => 'Withdrawal',
        'betslip_purchase' => 'Betslip purchase',
        'betslip_sale' => 'Betslip sale',
        'sale' => 'Sale',
        'purchase' => 'Purchase',
        'refund' => 'Refund',
        'payout' => 'Payout',
        'fee' => 'Platform fee',
        'platform_fee' => 'Platform fee',
        'admin_credit' => 'Admin credit',
        'admin_debit' => 'Admin debit',
    ];

    public function __construct()
    {
        $this->groups['fees']['requires_user_id'] = $this->platformUserId;
        $this->groups['seller_fees']['exclude_user_id'] = $this->platformUserId;
    }

    public function index(Request $request)
    {
        $base = Transaction::query();

        if ($search = $request->get('search')) {
            $base->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }
        if ($from = $request->get('from')) {
            $base->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $base->whereDate('created_at', '<=', $to);
        }

        // ── Group summary ────────────────────────────────────────────
        $groupStats = [];
        foreach ($this->groups as $key => $group) {
            $rows = (clone $base)->whereIn('type', $group['types']);

            if (isset($group['requires_user_id'])) {
                $rows->where('user_id', $group['requires_user_id']);
            }
            if (isset($group['exclude_user_id'])) {
                $rows->where('user_id', '!=', $group['exclude_user_id']);
            }
            if (isset($group['requires_negative_amount']) && $group['requires_negative_amount']) {
                $rows->where('amount', '<', 0);
            }

            $count  = (clone $rows)->count();
            $credit = (float) (clone $rows)->where('amount', '>', 0)->sum('amount');
            $debit  = (float) (clone $rows)->where('amount', '<', 0)->sum(DB::raw('ABS(amount)'));

            $groupStats[$key] = [
                'label'  => $group['label'],
                'count'  => $count,
                'credit' => $credit,
                'debit'  => $debit,
                'net'    => $credit - $debit,
            ];
        }

        // Aggregate from the corrected per-group totals
        $totalCount  = (clone $base)->count();
        $totalCredit = collect($groupStats)->sum('credit');
        $totalDebit  = collect($groupStats)->sum('debit');

        $summary = [
            'total_count'  => $totalCount,
            'total_credit' => $totalCredit,
            'total_debit'  => $totalDebit,
            'net_flow'     => $totalCredit - $totalDebit,
            'groups'       => $groupStats,
        ];

        // ── Results query ────────────────────────────────────────────
        $query = (clone $base)->with('user:id,name,code');

        $direction = $request->get('direction');
        if ($direction && isset($this->groups[$direction])) {
            $query->whereIn('type', $this->groups[$direction]['types']);

            if (isset($this->groups[$direction]['requires_user_id'])) {
                $query->where('user_id', $this->groups[$direction]['requires_user_id']);
            }
            if (isset($this->groups[$direction]['exclude_user_id'])) {
                $query->where('user_id', '!=', $this->groups[$direction]['exclude_user_id']);
            }
            if (isset($this->groups[$direction]['requires_negative_amount'])) {
                $query->where('amount', '<', 0);
            }
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->orderByDesc('created_at')
        ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn($tx) => [
                'id'             => $tx->id,
                'created_at'     => $tx->created_at->toISOString(),
                'created_time'   => $tx->created_at->format('H:i'),
                'created_date'   => $tx->created_at->format('Y-m-d'),
                'user'           => $tx->user ? [
                    'id'   => $tx->user->id,
                    'name' => $tx->user->name,
                    'code' => $tx->user->code,
                ] : null,
                'type'           => $tx->type,
                'type_label'     => $this->typeLabels[$tx->type] ?? $tx->type,
                'amount'         => (float) $tx->amount,
                'balance_before' => (float) $tx->balance_before,
                'balance_after'  => (float) $tx->balance_after,
                'reference'      => $tx->reference,
                'description'    => $tx->description,
            ]);

        $types = Transaction::select('type')->distinct()->pluck('type');

        return Inertia::render('admin/transactions/Index', [
            'transactions' => $transactions,
            'summary'      => $summary,
            'types'        => $types,
            'filters'      => [
                'type'      => $request->get('type', ''),
                'direction' => $direction,
                'search'    => $request->get('search', ''),
                'from'      => $request->get('from', ''),
                'to'        => $request->get('to', ''),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'transactions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Date', 'User Code', 'User Name', 'Type', 'Amount',
                'Balance Before', 'Balance After', 'Status', 'Reference', 'Description',
            ]);

            $query = Transaction::with('user:id,name,code');

            $direction = $request->get('direction');
            if ($direction && isset($this->groups[$direction])) {
                $query->whereIn('type', $this->groups[$direction]['types']);

                if (isset($this->groups[$direction]['requires_user_id'])) {
                    $query->where('user_id', $this->groups[$direction]['requires_user_id']);
                }
                if (isset($this->groups[$direction]['exclude_user_id'])) {
                    $query->where('user_id', '!=', $this->groups[$direction]['exclude_user_id']);
                }
                if (isset($this->groups[$direction]['requires_negative_amount'])) {
                    $query->where('amount', '<', 0);
                }
            }

            if ($type = $request->get('type')) {
                $query->where('type', $type);
            }
            if ($from = $request->get('from')) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to = $request->get('to')) {
                $query->whereDate('created_at', '<=', $to);
            }

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($u) => $u->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                });
            }

            $query->orderByDesc('created_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $tx) {
                    fputcsv($out, [
                        $tx->created_at->toDateTimeString(),
                        $tx->user?->code,
                        $tx->user?->name,
                        $tx->type,
                        $tx->amount,
                        $tx->balance_before,
                        $tx->balance_after,
                        $tx->status,
                        $tx->reference,
                        $tx->description,
                    ]);
                }
            });

            fclose($out);
        }, $filename);
    }
}