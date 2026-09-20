<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['user:id,name,code', 'resolver:id,name']);

        if ($status = $request->get('status', 'open')) {
            if ($status === 'open') {
                $query->whereIn('status', ['open', 'investigating']);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // $reports = $query->orderByRaw("FIELD(severity, 'critical','high','medium','low')")
        //     ->orderByDesc('created_at')
        //     ->paginate(25)
        //     ->withQueryString();

        $reports = $query
            ->orderByRaw("CASE severity 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
        ELSE 5 
    END")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'open' => Report::where('status', 'open')->count(),
            'investigating' => Report::where('status', 'investigating')->count(),
            'critical' => Report::whereIn('status', ['open', 'investigating'])->where('severity', 'critical')->count(),
            'resolved_today' => Report::where('status', 'resolved')->whereDate('resolved_at', today())->count(),
        ];

        return Inertia::render('admin/reports/Index', [
            'reports' => $reports,
            'counts' => $counts,
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    public function show(Report $report)
    {
        $report->load(['user', 'resolver']);

        // If betslip_code exists, load related betslip + purchases
        $betslip = null;
        if ($report->betslip_code) {
            $betslip = \App\Models\Betslip::with(['seller:id,name,code', 'buyers:id,name,code', 'purchases.buyer:id,name,code'])
                ->where('code', $report->betslip_code)
                ->first();
        }

        return Inertia::render('admin/reports/Show', [
            'report' => $report,
            'betslip' => $betslip,
        ]);
    }

    public function updateStatus(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,investigating,resolved,closed',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $report->resolution_notes,
            'resolved_by' => in_array($validated['status'], ['resolved', 'closed']) ? auth()->id() : null,
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed']) ? now() : null,
        ]);

        return back()->with('success', 'Report updated.');
    }

    /**
     * Force a refund on the underlying betslip.
     */
    public function forceRefund(Report $report)
    {
        if (!$report->betslip_code) {
            return back()->with('error', 'No betslip attached to this report.');
        }

        $betslip = \App\Models\Betslip::where('code', $report->betslip_code)->first();
        if (!$betslip) {
            return back()->with('error', 'Betslip not found.');
        }

        \DB::transaction(function () use ($betslip) {
            $walletService = app(\App\Services\WalletService::class);

            $purchases = $betslip->purchases()
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            foreach ($purchases as $purchase) {
                $walletService->refundEscrow(
                    user: $purchase->buyer,
                    amount: (float) $purchase->purchase_price,
                    context: "Betslip #{$betslip->code} (admin refund)",
                );
                $purchase->update(['status' => 'refunded']);
            }

            $betslip->update([
                'status' => 'settled',
                'is_winner' => false,
                'remaining' => 0,
            ]);
        });

        return back()->with('success', 'Betslip refunded and settled as loss.');
    }
}