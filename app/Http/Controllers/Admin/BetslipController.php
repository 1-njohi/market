<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Betslip;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BetslipController extends Controller
{
    public function index(Request $request)
    {
        $query = Betslip::with(['seller:id,name,code'])->withCount(['odds', 'purchases']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn($s) => $s->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Flagged filter — betslips that need admin eyes
        if ($request->boolean('flagged')) {
            $query->where(function ($q) {
                $q->where('total_odds', '>', 50)
                    ->orWhereHas('odds', fn($o) => $o, '>=', 8)
                    ->orWhere(function ($x) {
                        $x->where('status', 'pending')
                            ->where('created_at', '<', now()->subDays(14));
                    });
            });
        }

        $betslips = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'seller' => $b->seller ? [
                    'id' => $b->seller->id,
                    'name' => $b->seller->name,
                    'code' => $b->seller->code,
                ] : null,
                'price' => (float) $b->price,
                'total_odds' => (float) $b->total_odds,
                'status' => $b->status,
                'is_winner' => $b->is_winner,
                'legs_count' => $b->odds_count,
                'purchases_count' => $b->purchases_count,
                'created_at' => $b->created_at->toISOString(),
            ]);

        return Inertia::render('admin/betslips/Index', [
            'betslips' => $betslips,
            'filters' => $request->only(['search', 'status', 'flagged']),
        ]);
    }

    public function show(Betslip $betslip)
    {
        $betslip->load([
            'seller:id,name,code,email',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.market',
            'purchases.buyer:id,name,code',
        ]);

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
                'seller' => $betslip->seller,
            ],
            'legs' => $betslip->odds->map(fn($o) => [
                'id' => $o->id,
                'fixture' => [
                    'home' => $o->fixture->homeTeam->name ?? 'Unknown',
                    'away' => $o->fixture->awayTeam->name ?? 'Unknown',
                    'date' => $o->fixture->date,
                ],
                'market' => $o->market->name ?? 'Unknown',
                'selection' => $o->value,
                'odd' => (float) $o->odd,
                'status' => $o->status,
                'pivot_status' => $o->pivot->status,
            ]),
            'purchases' => $betslip->purchases->map(fn($p) => [
                'id' => $p->id,
                'buyer' => $p->buyer,
                'price' => (float) $p->purchase_price,
                'status' => $p->status,
                'purchased_at' => $p->created_at->toISOString(),
            ]),
        ]);
    }
}