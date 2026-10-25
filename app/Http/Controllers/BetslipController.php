<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Betslip;
use App\Models\Odd;
use App\Services\SellerEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Services\WatchlistService;
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

        \Log::info($oddIds);
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

        $canSell = app(SellerEligibilityService::class)->canSell(Auth::user());

        return Inertia::render('BetslipConfirm', [
            'selections' => $odds->map(fn($odd) => [
                'odd_id' => $odd->id,
                'odds' => (float) $odd->odd,
                'confidence' => 0,
                'selection' => $odd->value,
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
            'can_sell' => $canSell,
            'seller_progress' => app(SellerEligibilityService::class)->progress(Auth::user()),
            'seller_status' => Auth::user()->sellable_status,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'should_list' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:1|max:1000000',
            'caption' => 'nullable|string|max:280',
            'break_even_stake' => 'nullable|numeric|min:0',
            'odds' => ['required', 'array', 'min:1'],
            'odds.*.odd_id' => ['required', 'exists:odds,id'],
            'odds.*.confidence' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $draft = session('betslip_draft');

        if (!$draft || count($draft) === 0) {
            return Redirect::route('home')
                ->withErrors(['error' => 'Your draft expired. Please build the betslip again.']);
        }

        // Cross-check: the odd_ids the client submitted must match the
        // draft the user built. `$draft` is a flat list of odd_ids, so
        // we compare values (not keys).
        $submittedIds = collect($validated['odds'])
            ->pluck('odd_id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $draftIds = collect($draft)
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($submittedIds !== $draftIds) {
            return redirect()->back()->withErrors([
                'error' => 'The submitted betslip does not match your draft. Please review and try again.',
            ]);
        }

        $user = Auth::user();
        $canSell = app(SellerEligibilityService::class)->canSell($user);
        $shouldList = $canSell && ($validated['should_list'] ?? false);

        if ($shouldList && ($validated['price'] ?? null) === null) {
            return redirect()->back()->withErrors([
                'price' => 'A price is required to list this betslip.',
            ]);
        }

        DB::beginTransaction();

        try {
            $oddsCollection = Odd::whereIn('id', $draftIds)->get()->keyBy('id');

            if ($oddsCollection->count() !== count($draftIds)) {
                throw new \Exception('One or more selections are no longer available.');
            }

            $total_odds = $oddsCollection->reduce(fn($carry, $odd) => $carry * $odd->odd, 1);

            $serverBreakEven = $shouldList
                ? $this->computeBreakEvenStake((float) $validated['price'], (float) $total_odds)
                : null;

            if ($shouldList) {
                $clientBreakEven = $validated['break_even_stake'] ?? null;

                if (
                    $clientBreakEven !== null && $serverBreakEven !== null
                    && abs((float) $clientBreakEven - $serverBreakEven) > 1.0
                ) {
                    \Log::warning('Betslip break_even_stake drift', [
                        'client' => $clientBreakEven,
                        'server' => $serverBreakEven,
                        'price' => $validated['price'],
                        'total_odds' => $total_odds,
                    ]);
                }
            }

            $code = Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3);

            $betslip = Betslip::create([
                'user_id' => $user->id,
                'code' => strtoupper($code),
                'price' => $shouldList ? (float) $validated['price'] : 1,
                'total_odds' => $total_odds,
                'break_even_stake' => $serverBreakEven,
                'status' => 'pending',
                'remaining' => $oddsCollection->count(),
                'is_winner' => false,
                'caption' => $validated['caption'] ?? null,
                'is_sellable' => $shouldList,
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

            // Record a prediction for each leg
            $predictionRecorder = app(\App\Services\PredictionRecorderService::class);

            $confidenceByOddId = collect($validated['odds'])
                ->keyBy('odd_id')
                ->map(fn($row) => (int) $row['confidence']);

            foreach ($oddsCollection as $oddId => $oddModel) {
                $confidence = $confidenceByOddId->get($oddId);
                if ($confidence === null) {
                    continue;
                }

                $predictionRecorder->record(
                    userId: $user->id,
                    fixtureId: $oddModel->fixture_id,
                    marketId: $oddModel->market_id,
                    selection: $oddModel->value,
                    odds: (float) $oddModel->odd,
                    confidence: $confidence,
                    sourceType: 'betslip',
                    sourceId: (string) $betslip->id,
                );
            }

            DB::commit();

            session()->forget('betslip_draft');
            Cache::forget(DashboardController::cacheKey($user));

            $successMessage = $shouldList
                ? "Your betslip is live and listed for sale at KES "
                . number_format((float) $validated['price'], 2)
                . ". Tracking code " . $betslip->code . "."
                : "Your betslip is saved to your record. Tracking code "
                . $betslip->code . ".";

            return Redirect::route('betslip.success', ['code' => $betslip->code])
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Betslip creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to create betslip. Please try again.',
            ]);
        }
    }
    /**
     * List a previously non-sellable betslip for sale.
     *
     * The upgrade flow: a user who was ineligible at slip-creation time
     * becomes eligible later. Existing slips don't auto-list — the seller
     * deliberately picks which ones to sell and at what price. This
     * endpoint flips is_sellable true and (re)sets is_delisted false.
     *
     * Also serves as the relist action for delisted slips: same guards,
     * same outcome.
     */
    public function success(Request $request)
    {
        $betslip = Betslip::query()->where('code', $request->code)->firstOrFail();

        $formatted = $this->getBetslip($betslip->code, Auth::user());

        return Inertia::render('BetslipCreated', [
            'betslip' => $formatted,
        ]);
    }

    /**
     * Public lookup: given a tracking code, return the URL to view that
     * betslip. Used by the homepage "Enter Betslip Tracking Code" search.
     */
    public function lookup(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a tracking code.',
            ], 422);
        }

        $betslip = Betslip::where('code', $code)->first();

        if (!$betslip) {
            return response()->json([
                'success' => false,
                'message' => 'No betslip found with that code.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => $betslip->code,
            'url' => "/betslip/view/g/{$betslip->code}",
        ]);
    }
    public function show(Request $request)
    {
        $code = $request->code;

        $formattedBetslip = $this->getBetslip($code, Auth::user());

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $formattedBetslip,
            ]);
        }

        return Inertia::render('Betslip', [
            'betslip' => $formattedBetslip,
        ]);
    }

    /**
     * Build the full betslip payload consumed by resources/js/Pages/Betslip.vue
     */
    private function getBetslip(string $code, ?\App\Models\User $user): array
    {
        $slip = Betslip::with([
            'seller',
            'odds.market',
            'odds.fixture.league',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
        ])->where('code', $code)->firstOrFail();

        // ------------------------------------------------------------------
        // Access / watch state
        // ------------------------------------------------------------------
        $hasAccess = false;
        if ($user) {
            $hasAccess = (int) $slip->user_id === (int) $user->id
                || $slip->isPurchasedBy($user);
        }

        $isWatching = $user
            ? $slip->watchers()->where('user_id', $user->id)->exists()
            : false;

        // ------------------------------------------------------------------
        // Seller statistics — computed the same way the profile page does
        // ------------------------------------------------------------------
        $sellerStats = $this->computeSellerStats($slip->seller);

        // ------------------------------------------------------------------
        // Betslip statistics
        // ------------------------------------------------------------------
        $uniqueMarkets = $slip->odds->pluck('market_id')->unique()->count();

        $statistics = [
            'total_legs' => $slip->odds->count(),
            'unique_markets' => $uniqueMarkets,
        ];

        // ------------------------------------------------------------------
        // Purchase info
        // ------------------------------------------------------------------
        $price = (float) $slip->price;

        $purchaseInfo = [
            'price_to_purchase' => $price,
            'message' => match (true) {
                // Buyer / owner, slip still live
                $hasAccess && $slip->status === 'underway'
                => 'This slip is in play. Follow along as the remaining fixtures settle.',

                // Buyer / owner, slip settled as a win
                $hasAccess && $slip->status === 'settled' && $slip->is_winner
                => 'This slip won. Your payout has been settled.',

                // Buyer / owner, slip settled as a loss
                $hasAccess && $slip->status === 'settled'
                => 'This slip lost — your purchase was refunded automatically.',

                // Buyer / owner, still pending
                $hasAccess
                => 'You have full access to the picks and odds on this slip.',

                // Settled slips are public — non-buyers see picks now
                $slip->status === 'settled' && $slip->is_winner
                => 'This slip settled as a winner — the picks and results are public.',

                $slip->status === 'settled'
                => 'This slip settled as a loss — the picks, results and refunds are public.',

                // Live but locked
                $slip->status === 'underway'
                => 'This slip is in play. Unlock to see the picks and follow the action — full refund if it loses.',

                // Default: pending + locked
                default
                => 'Unlock this betslip to see the seller\'s actual picks. If the slip loses, you get your money back automatically.',
            },
        ];
        // ------------------------------------------------------------------
        // Correct outcome lookup for lost legs
        // key = "{fixture_id}-{market_id}" -> winning Odd
        // ------------------------------------------------------------------
        $correctOdds = Odd::query()
            ->whereIn('fixture_id', $slip->odds->pluck('fixture_id')->unique())
            ->whereIn('market_id', $slip->odds->pluck('market_id')->unique())
            ->where('status', 'won')
            ->get()
            ->keyBy(fn(Odd $o) => "{$o->fixture_id}-{$o->market_id}");

        // ------------------------------------------------------------------
        // Legs
        // ------------------------------------------------------------------
        $legs = $slip->odds->map(function (Odd $odd) use ($correctOdds) {
            $legStatus = $odd->pivot->status ?? 'pending';

            $leg = [
                'id' => $odd->id,
                'status' => $legStatus,
                'odds' => (float) ($odd->pivot->odd_value_at_time ?? $odd->odd),
                'selection' => $odd->value,
                'result_selections' => [], // <-- Default empty
                'market' => [
                    'id' => $odd->market_id,
                    'name' => $odd->market->name ?? '—',
                ],
                'fixture' => [
                    'id' => $odd->fixture_id,
                    'league' => $odd->fixture->league->name ?? '—',
                    'home_team' => $odd->fixture->homeTeam->name ?? 'Home',
                    'away_team' => $odd->fixture->awayTeam->name ?? 'Away',
                    'status' => $odd->fixture->status_short ?? 'NS',
                ],
            ];

            // Attach the actual winning selection if the market is settled
            $win = $correctOdds->get("{$odd->fixture_id}-{$odd->market_id}");
            if ($win) {
                $leg['result_selections'] = [$win->value];
            }
            if ($legStatus === 'lost') {
                $win = $correctOdds->get("{$odd->fixture_id}-{$odd->market_id}");
                if ($win) {
                    $leg['correct_selection'] = $win->value;
                    $leg['correct_odds'] = (float) $win->odd;
                }
            }

            return $leg;
        })->values()->all();

        $legs = $this->attachCrowdWisdomToLegs($legs);
        // ------------------------------------------------------------------
        // Final payload — matches every binding in Betslip.vue
        // ------------------------------------------------------------------
        return [
            'id' => $slip->id,
            'code' => $slip->code,
            'status' => $slip->status,
            'is_winner' => (bool) $slip->is_winner,
            'total_odds' => (float) $slip->total_odds,
            'has_access' => $hasAccess,
            'can_watch' => $slip->status !== 'settled' && $slip->is_sellable,
            'is_watching' => $isWatching,
            'is_owner' => $user ? (int) $slip->user_id === (int) $user->id : false, // ← new

            'statistics' => $statistics,

            'seller' => [
                'id' => $slip->seller->id,
                'code' => $slip->seller->code,
                'name' => $slip->seller->name,
                'avatar' => $slip->seller->profile_picture_url
                    ?? $slip->seller->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($slip->seller->name),
                'member_since' => optional($slip->seller->created_at)->format('M Y'),
                'prediction_points' => (int) $slip->seller->prediction_points,
                'global_rank' => $slip->seller->prediction_points > 0
                    ? User::where('prediction_points', '>', $slip->seller->prediction_points)->count() + 1
                    : null,
                'statistics' => $sellerStats,
            ],

            'purchase_info' => $purchaseInfo,

            'legs' => $legs,
        ];
    }

    /**
     * Compute the seller's public performance stats.
     * Shape mirrors what Betslip.vue binds to:
     *   slip.seller.statistics.{total_betslips,won_betslips,win_rate,roi,recent_form}
     */
    private function computeSellerStats(\App\Models\User $seller): array
    {
        // Only settled slips count toward win rate / ROI / recent form.
        $settled = Betslip::query()
            ->where('user_id', $seller->id)
            ->where('status', 'settled')
            ->orderByDesc('settled_at')
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'status',
                'is_winner',
                'total_odds',
                'price',
                'settled_at',
                'updated_at',
            ]);

        $totalBetslips = $settled->count();
        $wonBetslips = $settled->where('is_winner', true)->count();

        $winRate = $totalBetslips > 0
            ? round(($wonBetslips / $totalBetslips) * 100, 1)
            : 0.0;

        // ROI: treat the slip price as what buyers paid, and a winning
        // payout as price × total_odds. Non-winners return 0.
        $staked = 0.0;
        $returns = 0.0;

        foreach ($settled as $s) {
            $stake = (float) $s->price;
            $staked += $stake;

            if ($s->is_winner) {
                $returns += $stake * (float) $s->total_odds;
            }
        }

        $roi = $staked > 0
            ? round((($returns - $staked) / $staked) * 100, 1)
            : 0.0;

        // Recent form — most recent first, capped at 8 to match the UI dots.
        $recentForm = $settled
            ->take(8)
            ->map(fn($s) => $s->is_winner ? 'W' : 'L')
            ->values()
            ->all();

        return [
            'total_betslips' => $totalBetslips,
            'won_betslips' => $wonBetslips,
            'win_rate' => $winRate,
            'roi' => $roi,
            'recent_form' => $recentForm,
        ];
    }
    /**
     * Calculate seller statistics
     */
    private function calculateSellerStats($seller)
    {
        // Get all settled betslips
        $settledBetslips = $seller->betslips()
            ->whereIn('status', ['settled', 'voided'])
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
    private function formatBetslipForDisplay(
        $betslip,
        $sellerStats,
        $isSeller,
        $hasPurchased,
        bool $isWatching = false,
        bool $canWatch = false,
    ) {
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

            //Watch information 
            'is_watching' => $isWatching,
            'can_watch' => $canWatch,

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

    /**
     * Stake a buyer must place at total_odds to earn back the price
     * they paid for this slip:
     *
     *     profit = stake × (total_odds − 1)  must equal  price
     *     ⇒ stake = price / (total_odds − 1)
     *
     * Returns null when total_odds ≤ 1.0 — the equation has no
     * solution. Real odds are always > 1.0, so this only fires on
     * corrupted data.
     */
    private function computeBreakEvenStake(float $price, float $totalOdds): ?float
    {
        if ($totalOdds <= 1.0) {
            return null;
        }

        return round($price / ($totalOdds - 1), 2);
    }

    /**
     * Attach crowd wisdom to every leg of the given betslip.
     * Returns the legs array with `crowd_wisdom` key added.
     *
     * Caller decides whether to *show* the payload — the flag
     * `is_visible` and the raw distributions are always attached; the
     * frontend hides the data when hasAccess is false.
     *
     * @param  array<int, array>  $legs
     * @return array<int, array>
     */
    private function attachCrowdWisdomToLegs(array $legs): array
    {
        $service = app(\App\Services\CrowdWisdomService::class);

        // The legs array in the `show` path already has fixture + market
        // ids nested. For `success` (which reuses show's structure) this
        // works too.
        foreach ($legs as &$leg) {
            $fixtureId = (int) ($leg['fixture']['id'] ?? 0);
            $marketId = (int) ($leg['market']['id'] ?? 0);

            if ($fixtureId === 0 || $marketId === 0) {
                $leg['crowd_wisdom'] = null;
                continue;
            }

            $leg['crowd_wisdom'] = $service->forFixture($fixtureId, $marketId);
        }

        return $legs;
    }
}
