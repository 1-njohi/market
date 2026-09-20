<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Models\Odd;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Cache;

class BetslipController extends Controller
{
    public function draft(Request $request)
    {
        $validated = $request->validate([
            'selections' => 'required|array|min:1',
            'selections.*.odd_id' => 'required|exists:odds,id',
        ]);

        // De-dupe in case the user somehow double-clicked
        $oddIds = collect($validated['selections'])->pluck('odd_id')->unique()->values()->toArray();

        session(['betslip_draft' => $oddIds]);

        return Redirect::route('betslip.confirm');
    }

    public function confirm(Request $request)
    {
        $oddIds = session('betslip_draft');

        if (!$oddIds || count($oddIds) === 0) {
            return Redirect::route('home')
                ->with('error', 'Your betslip draft expired. Please build it again.');
        }

        $odds = Odd::with([
            'fixture.homeTeam:id,id_on_api,name',
            'fixture.awayTeam:id,id_on_api,name',
            'fixture.league:id,name,country',
            'market:id,name',
        ])->whereIn('id', $oddIds)->get();

        if ($odds->isEmpty()) {
            session()->forget('betslip_draft');
            return Redirect::route('home')
                ->with('error', 'Those selections are no longer available.');
        }

        $totalOdds = $odds->reduce(fn($carry, $odd) => $carry * $odd->odd, 1);

        return Inertia::render('BetslipConfirm', [
            'selections' => $odds->map(fn($odd) => [
                'odd_id' => $odd->id,
                'odds' => (float) $odd->odd,
                'market' => $odd->market->name ?? 'Unknown Market',
                'home_team' => $odd->fixture->homeTeam->name ?? 'Unknown',
                'away_team' => $odd->fixture->awayTeam->name ?? 'Unknown',
                'league' => $odd->fixture->league->name ?? null,
                'country' => $odd->fixture->league->country ?? null,
                'kickoff' => $odd->fixture->date
                    ? \Carbon\Carbon::parse($odd->fixture->date)->format('d/m/y - H:i')
                    : null,
            ])->values(),
            'total_odds' => round($totalOdds, 2),
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:1|max:1000000',
            'caption' => 'nullable|string|max:280',
        ]);

        $oddIds = session('betslip_draft');

        if (!$oddIds || count($oddIds) === 0) {
            return Redirect::route('home')
                ->withErrors(['error' => 'Your draft expired. Please build the betslip again.']);
        }

        DB::beginTransaction();

        try {
            $oddsCollection = Odd::whereIn('id', $oddIds)->get()->keyBy('id');

            if ($oddsCollection->count() !== count($oddIds)) {
                throw new \Exception('One or more selections are no longer available.');
            }

            $total_odds = $oddsCollection->reduce(fn($carry, $odd) => $carry * $odd->odd, 1);

            $code = Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3);

            $betslip = Betslip::create([
                'user_id' => Auth::id(),
                'code' => strtoupper($code),
                'price' => $validated['price'],
                'total_odds' => $total_odds,
                'status' => 'pending',
                'remaining' => $oddsCollection->count(),
                'is_winner' => false,
                'caption' => $validated['caption'] ?? null,
            ]);

            $attachData = [];
            foreach ($oddsCollection as $oddId => $oddModel) {
                $attachData[$oddId] = [
                    'odd_value_at_time' => $oddModel->odd,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $betslip->odds()->attach($attachData);

            DB::commit();

            session()->forget('betslip_draft');
            Cache::forget(DashboardController::cacheKey(Auth::user()));

            $successMessage = "Your Betslip has been created successfully. It was assigned the tracking code "
                . $betslip->code . ". You can share it with potential buyers";

            return Redirect::route('betslip.success', ['code' => $betslip->code])
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to create betslip. Please try again.',
            ]);
        }
    }
    public function success(Request $request)
    {
        $betslip = Betslip::query()->where('code', $request->code)->first();
        return Inertia::render('BetslipCreated', [
            'betslip' => $betslip
        ]);
    }

    public function show(Request $request)
    {
        $code = $request->code;
        if (Auth::user()) {
            $formattedBetslip = $this->getBetslip($code, Auth::user());
        } else {
            $formattedBetslip = $this->getBetslip($code, null);
        }

        // If API request, return JSON
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $formattedBetslip
            ]);
        }

        // If Inertia request, render view
        return Inertia::render('Betslip', [
            'betslip' => $formattedBetslip
        ]);
    }

    /**
     * Display a single betslip for potential buyers
     */
    public function getBetslip($code, $user)
    {
        // Load the betslip with all necessary relationships
        $betslip = Betslip::with([
            'seller' => function ($query) {
                $query->select('id', 'name', 'email', 'created_at', 'code');
            },
            'odds' => function ($query) {
                $query->select('odds.id', 'odds.fixture_id', 'odds.market_id', 'odds.value', 'odds.odd')
                    ->withPivot('odd_value_at_time', 'status');
            },
            'odds.fixture' => function ($query) {
                $query->select('id', 'id_on_api', 'date', 'timestamp', 'league_id', 'home_team_id', 'away_team_id');
            },
            'odds.fixture.homeTeam' => function ($query) {
                $query->select('id', 'name');
            },
            'odds.fixture.awayTeam' => function ($query) {
                $query->select('id', 'name');
            },
            'odds.fixture.league' => function ($query) {
                $query->select('id', 'name', 'country');
            },
            'odds.market' => function ($query) {
                $query->select('id', 'name');
            },
            'buyers' // Load buyers relationship
        ])
            ->where('code', $code)
            ->firstOrFail();

        // Check if the logged-in user is the seller or has purchased the betslip
        if ($user) {
            $isSeller = $user && $betslip->user_id === $user->id;
            $hasPurchased = $user && $betslip->buyers()->where('buyer_id', $user->id)->exists();
        } else {
            $isSeller = false;
            $hasPurchased = false;
        }
        // Calculate seller statistics
        $sellerStats = $this->calculateSellerStats($betslip->seller);

        // Format the betslip for response
        $formattedBetslip = $this->formatBetslipForDisplay($betslip, $sellerStats, $isSeller, $hasPurchased);

        return $formattedBetslip;
    }

    /**
     * Calculate seller statistics
     */
    private function calculateSellerStats($seller)
    {
        // Get all settled betslips
        $settledBetslips = $seller->betslips()
            ->whereIn('status', ['settled', 'vioded'])
            ->get();

        $totalBetslips = $settledBetslips->count();
        $wonBetslips = $settledBetslips->where('is_winner', true)->count();
        $totalStaked = $settledBetslips->sum('price');
        $totalWon = $settledBetslips->where('is_winner', true)->sum('price');

        // Win rate
        $winRate = $totalBetslips > 0 ? round(($wonBetslips / $totalBetslips) * 100) : 0;

        // ROI (Return on Investment)
        $roi = $totalStaked > 0 ? round(($totalWon / $totalStaked) * 100, 1) : 0;

        // Recent form (last 6 settled betslips)
        $recentForm = $seller->betslips()
            ->whereIn('status', ['settled', 'voided'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($betslip) {
                return $betslip->is_winner ? 'W' : 'L';
            })
            ->toArray();

        // Pad with 'P' (Pending) if less than 6
        while (count($recentForm) < 6) {
            array_unshift($recentForm, 'P');
        }

        return [
            'total_betslips' => $totalBetslips,
            'won_betslips' => $wonBetslips,
            'win_rate' => $winRate,
            'roi' => $roi,
            'recent_form' => $recentForm,
            'total_staked' => $totalStaked,
            'total_won' => $totalWon,
        ];
    }

    /**
     * Format betslip for display to potential buyers
     */
    private function formatBetslipForDisplay($betslip, $sellerStats, $isSeller, $hasPurchased)
    {
        // Determine if user has access to view odds (seller or purchaser)
        $hasAccess = $isSeller || $hasPurchased;

        // Get unique markets count
        $uniqueMarkets = $betslip->odds->pluck('market_id')->unique()->count();

        // Calculate total odds (should already be stored, but recalculate if needed)
        $totalOdds = $betslip->total_odds ?? $betslip->odds->reduce(function ($carry, $odd) {
            return $carry * ($odd->pivot->odd_value_at_time ?? $odd->odd);
        }, 1);

        // Determine if betslip is still active
        $isActive = $betslip->status === 'pending' && $betslip->remaining > 0;

        return [
            // Betslip basic info
            'id' => $betslip->id,
            'code' => $betslip->code,
            'price' => (float) $betslip->price,
            'total_odds' => round($totalOdds, 2),
            'status' => $betslip->status,
            'is_winner' => $betslip->is_winner,
            'remaining' => $betslip->remaining,
            'is_active' => $isActive,
            'has_access' => $hasAccess, // Whether user can view odds
            'created_at' => $betslip->created_at->toISOString(),
            'updated_at' => $betslip->updated_at->toISOString(),

            // Seller information
            'seller' => [
                'id' => $betslip->seller->id,
                'name' => $betslip->seller->name,
                'email' => $betslip->seller->email,
                'member_since' => $betslip->seller->created_at->format('F Y'),
                'code' => $betslip->seller->code,
                'statistics' => $sellerStats,
            ],

            // Betslip statistics
            'statistics' => [
                'total_legs' => $betslip->odds->count(),
                'unique_markets' => $uniqueMarkets,
                'total_price' => (float) $betslip->price,
                'potential_payout' => round($betslip->price * $totalOdds, 2),
            ],

            // Legs (individual selections)
            'legs' => $betslip->odds->map(function ($odd) use ($hasAccess) {
                $fixture = $odd->fixture;              // already eager-loaded
                $homeTeam = $fixture?->homeTeam;        // already eager-loaded
                $awayTeam = $fixture?->awayTeam;

                return [
                    'id' => $odd->id,
                    'fixture' => [
                        'id' => $fixture?->id,
                        'date' => $fixture?->date
                            ? \Carbon\Carbon::parse($fixture->date)->format('d/m/y - H:i')
                            : null,
                        'timestamp' => $fixture?->timestamp,
                        'home_team' => $homeTeam?->name ?? 'Unknown',
                        'away_team' => $awayTeam?->name ?? 'Unknown',
                        'league' => $fixture?->league->name ?? 'Unknown League',
                        'country' => $fixture?->league->country,
                    ],
                    'market' => [
                        'id' => $odd->market->id ?? $odd->market_id,
                        'name' => $odd->market->name ?? 'Unknown Market',
                    ],
                    'selection' => $hasAccess ? ($odd->pivot->selection_value ?? $odd->value) : 'locked',
                    'odds' => $hasAccess
                        ? (float) ($odd->pivot->odd_value_at_time ?? $odd->odd)
                        : 'locked',
                    'status' => $odd->pivot->status ?? $odd->status ?? 'pending',
                ];
            })->toArray(),

            // For buyers - purchase information
            'purchase_info' => [
                'can_purchase' => $isActive && !$isSeller && !$hasPurchased,
                'price_to_purchase' => (float) $betslip->price,
                'potential_winning' => round($betslip->price * $totalOdds, 2),
                'message' => $this->getPurchaseMessage($isActive, $isSeller, $hasPurchased),
            ],
        ];
    }

    /**
     * Get appropriate purchase message based on user status
     */
    private function getPurchaseMessage($isActive, $isSeller, $hasPurchased)
    {
        if ($isSeller) {
            return 'You are the seller of this betslip';
        }

        if ($hasPurchased) {
            return 'You have already purchased this betslip';
        }

        if ($isActive) {
            return 'This betslip is available for purchase';
        }

        return 'This betslip is no longer available';
    }
}
