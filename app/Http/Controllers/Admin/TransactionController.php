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
    /**
     * Money-in / money-out type groupings.
     * Add new transaction types here and every tab, count, and export updates automatically.
     */
    protected array $creditTypes = [
        'deposit',
        'betslip_sale',
        'sale',
        'refund',
        'admin_credit',
        'payout',
    ];

    protected array $debitTypes = [
        'withdrawal',
        'betslip_purchase',
        'purchase',
        'admin_debit',
    ];

    protected array $adjustmentTypes = [
        'admin_credit',
        'admin_debit',
    ];

    public function index(Request $request)
    {
        // ── Base query (search + date range) — reused for counts and results ──
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

        // ── Summary counts (respect search + dates, ignore the tab itself) ──
        $creditCount = (clone $base)->whereIn('type', $this->creditTypes)->count();
        $creditVolume = (float) (clone $base)->whereIn('type', $this->creditTypes)->sum('amount');
        $debitCount = (clone $base)->whereIn('type', $this->debitTypes)->count();
        $debitVolume = (float) (clone $base)->whereIn('type', $this->debitTypes)->sum(DB::raw('ABS(amount)'));

        $counts = [
            'total_count' => (clone $base)->count(),
            'total_volume' => $creditVolume + $debitVolume,
            'credit_count' => $creditCount,
            'credit_volume' => $creditVolume,
            'debit_count' => $debitCount,
            'debit_volume' => $debitVolume,
            'net' => $creditVolume - $debitVolume,
        ];

        // ── Main results query ──
        $query = (clone $base)->with('user:id,name,code');

        // Direction tab
        $direction = $request->get('direction');
        match ($direction) {
            'credits' => $query->whereIn('type', $this->creditTypes),
            'debits' => $query->whereIn('type', $this->debitTypes),
            'adjustments' => $query->whereIn('type', $this->adjustmentTypes),
            default => null,
        };

        // Legacy type filter (still honoured for deep-links and CSV)
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $types = Transaction::select('type')->distinct()->pluck('type');

        return Inertia::render('admin/transactions/Index', [
            'transactions' => $transactions,
            'types' => $types,
            'counts' => $counts,
            'filters' => [
                'type' => $request->get('type', ''),
                'direction' => $request->get('direction', ''),
                'search' => $request->get('search', ''),
                'from' => $request->get('from', ''),
                'to' => $request->get('to', ''),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'transactions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User Code', 'User Name', 'Type', 'Amount', 'Balance Before', 'Balance After', 'Status', 'Reference', 'Description']);

            $query = Transaction::with('user:id,name,code');

            // Direction filter (mirrors the tabs)
            match ($request->get('direction')) {
                'credits' => $query->whereIn('type', $this->creditTypes),
                'debits' => $query->whereIn('type', $this->debitTypes),
                'adjustments' => $query->whereIn('type', $this->adjustmentTypes),
                default => null,
            };

            if ($type = $request->get('type'))
                $query->where('type', $type);
            if ($from = $request->get('from'))
                $query->whereDate('created_at', '>=', $from);
            if ($to = $request->get('to'))
                $query->whereDate('created_at', '<=', $to);

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