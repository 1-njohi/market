<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Betslip;
use App\Services\PlatformFeeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BetslipController extends Controller
{
    public function index(Request $request, PlatformFeeService $feeService)
    {
        $query = Betslip::query()
            ->with(['seller:id,name,code'])
            ->withCount(['odds', 'purchases'])
            ->withSum('purchases', 'purchase_price');

        // ── 1. Filters (unchanged) ──────────────────────────────────
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn($s) => $s->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if ($sellerId = $request->get('seller_id')) {
            $query->where('user_id', $sellerId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($result = $request->get('result')) {
            if ($result === 'won') {
                $query->where('status', 'settled')->where('is_winner', true);
            } elseif ($result === 'lost') {
                $query->where('status', 'settled')->where('is_winner', false);
            } elseif ($result === 'voided') {
                $query->where('status', 'voided');
            }
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($minOdds = $request->get('min_odds')) {
            $query->where('total_odds', '>=', (float) $minOdds);
        }
        if ($maxOdds = $request->get('max_odds')) {
            $query->where('total_odds', '<=', (float) $maxOdds);
        }

        if ($minPurchases = $request->get('min_purchases')) {
            $query->has('purchases', '>=', (int) $minPurchases);
        }
        if ($request->get('max_purchases') === '0') {
            $query->doesntHave('purchases');
        }

        if ($request->boolean('flagged')) {
            $query->where(function ($q) {
                $q->where(function ($x) {
                    $x->where('status', 'pending')->where('created_at', '<', now()->subDays(14));
                })
                    ->orWhere('total_odds', '>', 50)
                    ->orWhereHas('seller', function ($s) {
                        $s->whereHas('Betslips', fn($b) => $b->where('created_at', '>=', now()->subDay()), '>=', 50);
                    });
            });
        }

        // ── 2. Summary Data (Safe SQL) ──────────────────────────────
        // Clone the query so we don't paginate the summary.
        // We need the IDs of the filtered betslips to calculate volume.
        $filteredIds = (clone $query)->pluck('id');

        $totalCount = $filteredIds->count();

        // Query the purchases table directly to avoid the SQL alias collision.
        // This is extremely fast because we use the indexed `betslip_id`.
        $totalVolume = (float) \App\Models\BetslipUserPurchase::whereIn('betslip_id', $filteredIds)
            ->sum('purchase_price');

        // Run a grouped query just for the win/loss counts.
        $statusCounts = (clone $query)
            ->selectRaw('
                SUM(CASE WHEN status = "settled" AND is_winner = 1 THEN 1 ELSE 0 END) as won_count,
                SUM(CASE WHEN status = "settled" AND is_winner = 0 THEN 1 ELSE 0 END) as lost_count
            ')
            ->first();

        $estimatedEarned = $totalVolume * 0.10; // Global average estimate

        $summary = [
            'total_count' => $totalCount,
            'total_volume' => $totalVolume,
            'total_earned' => $estimatedEarned,
            'won_count' => (int) ($statusCounts->won_count ?? 0),
            'lost_count' => (int) ($statusCounts->lost_count ?? 0),
        ];

        // ── 3. Sorting & Pagination ─────────────────────────────────
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'odds_desc' => $query->orderByDesc('total_odds'),
            'odds_asc' => $query->orderBy('total_odds'),
            'price_desc' => $query->orderByDesc('price'),
            'price_asc' => $query->orderBy('price'),
            'sales_desc' => $query->orderByDesc('purchases_count'),
            // Note: SQLite sometimes struggles sorting by `withSum` aliases. 
            // The safest fallback is to sort by the actual column or a subquery.
            'volume_desc' => $query->orderByDesc('purchases_sum_purchase_price'),
            'earned_desc' => $query->orderByDesc('purchases_sum_purchase_price'),
            default => $query->orderByDesc('created_at'),
        };

        $betslips = $query->paginate(25)->withQueryString()->through(function ($b) use ($feeService) {
            $salesValue = (float) ($b->purchases_sum_purchase_price ?? 0);

            $feePercent = $b->seller ? $feeService->getFeePercentageFor($b->seller) : 0.10;
            $platformEarned = $salesValue * $feePercent;

            return [
                'id' => $b->id,
                'code' => $b->code,
                'seller' => $b->seller,
                'price' => (float) $b->price,
                'total_odds' => (float) $b->total_odds,
                'status' => $b->status,
                'is_winner' => $b->is_winner,
                'legs_count' => $b->odds_count,
                'purchases_count' => $b->purchases_count,
                'sales_value' => $salesValue,
                'platform_earned' => $platformEarned,
                'created_at' => $b->created_at->toISOString(),
            ];
        });

        return Inertia::render('admin/betslips/Index', [
            'betslips' => $betslips,
            'summary' => $summary,
            'filters' => $request->only([
                'search',
                'seller_id',
                'status',
                'from',
                'to',
                'min_odds',
                'max_odds',
                'min_purchases',
                'max_purchases',
                'flagged',
                'sort',
                'result'
            ]),
        ]);
    }
    public function show(Betslip $betslip, PlatformFeeService $feeService)
    {
        $betslip->load([
            'seller:id,name,code,email',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.market',
            'purchases' => function ($q) {
                $q->with('buyer:id,name,code')->latest()->limit(50);
            },
        ]);

        $totalSales = (float) $betslip->purchases->sum('purchase_price');

        // Calculate fee based on seller's tier
        $feePercent = $betslip->seller ? $feeService->getFeePercentageFor($betslip->seller) : 0.10;
        $feeAmount = $totalSales * $feePercent;

        // Determine state (Tense logic)
        $isSettled = $betslip->status === 'settled';
        $isWon = $betslip->is_winner;

        $feeLabel = 'Projected Platform Fees';
        $feeValue = $feeAmount;
        $feeColor = 'sky'; // Pending/Active

        if ($isSettled) {
            if ($isWon) {
                $feeLabel = 'Platform Revenue Realized';
                $feeColor = 'emerald';
            } else {
                $feeLabel = 'Revenue Forfeited (Lost)';
                $feeValue = 0;
                $feeColor = 'rose';
            }
        } elseif ($betslip->status === 'voided') {
            $feeLabel = 'Revenue Forfeited (Void)';
            $feeValue = 0;
            $feeColor = 'slate';
        }

        return Inertia::render('admin/betslips/Show', [
            'betslip' => [
                'id' => $betslip->id,
                'code' => $betslip->code,
                'price' => (float) $betslip->price,
                'total_odds' => (float) $betslip->total_odds,
                'status' => $betslip->status,
                'is_winner' => $betslip->is_winner,
                'remaining' => $betslip->remaining,
                'created_at' => $betslip->created_at->toISOString(),
                'seller' => $betslip->seller ?: ['name' => '(deleted)'],
            ],
            'financials' => [
                'total_sales' => $totalSales,
                'fee_label' => $feeLabel,
                'fee_value' => $feeValue,
                'fee_color' => $feeColor,
            ],
            'legs' => $betslip->odds->map(fn($o) => [
                'id' => $o->id,
                'fixture' => [
                    'home' => $o->fixture->homeTeam->name ?? 'Unknown',
                    'away' => $o->fixture->awayTeam->name ?? 'Unknown',
                    'date' => $o->fixture->date ?? null,
                ],
                'market' => $o->market->name ?? 'Unknown',
                'selection' => $o->value,
                'odd' => (float) $o->odd,
                'status' => $o->status,
                'pivot_status' => $o->pivot->status,
            ]),
            'purchases' => $betslip->purchases->map(fn($p) => [
                'id' => $p->id,
                'buyer' => $p->buyer ?: ['name' => '(deleted)'],
                'price' => (float) $p->purchase_price,
                'status' => $p->status,
                'purchased_at' => $p->created_at->toISOString(),
            ]),
        ]);
    }
}