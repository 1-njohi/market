<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('user:id,name,code');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('code', 'like', "%{$search}%")
                                                       ->orWhere('name', 'like', "%{$search}%"));
            });
        }
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $types = Transaction::select('type')->distinct()->pluck('type');

        return Inertia::render('admin/transactions/Index', [
            'transactions' => $transactions,
            'types' => $types,
            'filters' => $request->only(['type', 'search', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'transactions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User Code', 'User Name', 'Type', 'Amount', 'Balance Before', 'Balance After', 'Status', 'Reference', 'Description']);

            $query = Transaction::with('user:id,name,code');
            if ($type = $request->get('type')) $query->where('type', $type);
            if ($from = $request->get('from')) $query->whereDate('created_at', '>=', $from);
            if ($to = $request->get('to')) $query->whereDate('created_at', '<=', $to);

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