<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Fixture;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Prediction;
use App\Services\CrowdWisdomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FixtureResultsController extends Controller
{
    public function show(Fixture $fixture, CrowdWisdomService $crowdWisdom): Response
    {
        if (!in_array($fixture->status_short, ['FT', 'AET', 'PEN'])) {
            abort(404, 'Fixture results are not available yet.');
        }

        $fixture->load(['homeTeam', 'awayTeam', 'league']);

        $marketIds = Odd::where('fixture_id', $fixture->id)->distinct()->pluck('market_id');
        $markets = Market::whereIn('id', $marketIds)->get();

        $marketResults = [];

        foreach ($markets as $market) {
            // 1. Get winning odd(s)
            $winningOdds = Odd::where('fixture_id', $fixture->id)
                ->where('market_id', $market->id)
                ->where('status', 'won')
                ->get();

            $winningSelections = $winningOdds->pluck('value')->toArray();
            $winningOddsValue = $winningOdds->first()?->odd;

            // 2. Get crowd wisdom
            $cw = $crowdWisdom->forFixture($fixture->id, $market->id);

            // 3. Count betslips that included this market
            $marketPopularity = DB::table('betslip_odd')
                ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
                ->where('odds.fixture_id', $fixture->id)
                ->where('odds.market_id', $market->id)
                ->distinct('betslip_odd.betslip_id')
                ->count('betslip_odd.betslip_id');

            $marketResults[] = [
                'id' => $market->id,
                'name' => $market->name,
                'winning_selections' => $winningSelections,
                'winning_odds' => $winningOddsValue ? (float) $winningOddsValue : null,
                'crowd_wisdom' => $cw,
                'market_popularity' => $marketPopularity,
            ];
        }

        // ── Fixture-level talking points ──────────────────────────────
        $fixtureCrowdStats = $this->computeFixtureCrowdStats($markets, $marketResults);

        // 1. Betslip IDs that included this fixture
        $betslipIds = DB::table('betslip_odd')
            ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
            ->where('odds.fixture_id', $fixture->id)
            ->distinct('betslip_odd.betslip_id')
            ->pluck('betslip_odd.betslip_id');

        $totalBetslips = $betslipIds->count();

        // 2. Total Sales Volume (what buyers paid to unlock slips on this fixture)
        $totalSalesVolume = (float) BetslipUserPurchase::whereIn('betslip_id', $betslipIds)
            ->sum('purchase_price');

        // 3. Total Seller Earnings (sum of listed prices for winning slips
        //    that had buyers). We sum the *listed price* (betslip.price),
        //    not the purchase price, because that's the seller's gross.
        $winningSlipIds = Betslip::whereIn('id', $betslipIds)
            ->where('status', 'settled')
            ->where('is_winner', true)
            ->pluck('id');

        $sellerEarnings = (float) Betslip::whereIn('id', $winningSlipIds)
            ->whereHas('purchases')
            ->sum('price');

        // 4. Biggest and riskiest betslip
        $biggestBetslip = Betslip::whereIn('id', $betslipIds)
            ->orderByDesc('total_odds')
            ->first(['id', 'code', 'total_odds']);

        // 5. Total crowd voters (dedupe users across markets)
        $totalCrowdVoters = Prediction::where('fixture_id', $fixture->id)
            ->whereIn('market_id', $markets->pluck('id'))
            ->distinct()
            ->count('user_id');

        // 6. Match narrative derived from score
        $narrative = $this->buildMatchNarrative($fixture);

        return Inertia::render('Fixtures/Results', [
            'fixture' => [
                'id' => $fixture->id,
                'home_team' => $fixture->homeTeam->name,
                'away_team' => $fixture->awayTeam->name,
                'league' => $fixture->league->name,
                'date' => $fixture->date->toISOString(),
                'score' => [
                    'home' => $fixture->goals_home,
                    'away' => $fixture->goals_away,
                ],
                'status' => $fixture->status_short,
            ],
            'markets' => $marketResults,
            'fixture_talking_points' => [
                'crowd' => $fixtureCrowdStats,
                'betslips' => [
                    'count'           => $totalBetslips,
                    'sales_volume'    => $totalSalesVolume,
                    'seller_earnings' => $sellerEarnings,
                    'winning_slips'   => $winningSlipIds->count(),
                    'biggest_slip'    => $biggestBetslip ? [
                        'code'       => $biggestBetslip->code,
                        'total_odds' => (float) $biggestBetslip->total_odds,
                    ] : null,
                ],
                'crowd_voters' => $totalCrowdVoters,
                'narrative'    => $narrative,
            ],
        ]);
    }

    /**
     * @return array{
     *     markets_evaluated: int,
     *     crowd_correct: int,
     *     crowd_accuracy: float|null,
     *     biggest_hit: array|null,
     *     biggest_miss: array|null,
     * }
     */
    private function computeFixtureCrowdStats($markets, array $marketResults): array
    {
        $evaluated = 0;
        $correct = 0;
        $biggestHit = null;
        $biggestMiss = null;

        foreach ($marketResults as $result) {
            $cw = $result['crowd_wisdom'];
            if (! ($cw['is_visible'] ?? false)) {
                continue;
            }

            $entries = collect($cw['all'] ?? [])->sortDesc();
            if ($entries->isEmpty()) {
                continue;
            }

            $topSelection = $entries->keys()->first();
            $topPct = (int) $entries->first();

            $winner = $result['winning_selections'][0] ?? null;
            if ($winner === null) {
                continue;
            }

            $evaluated++;
            $crowdWon = in_array($topSelection, $result['winning_selections'], true);
            if ($crowdWon) {
                $correct++;
            }

            $candidate = [
                'market'    => $result['name'],
                'selection' => $topSelection,
                'pct'       => $topPct,
                'winner'    => $winner,
            ];

            if ($crowdWon && ($biggestHit === null || $topPct > $biggestHit['pct'])) {
                $biggestHit = $candidate;
            }
            if (! $crowdWon && ($biggestMiss === null || $topPct > $biggestMiss['pct'])) {
                $biggestMiss = $candidate;
            }
        }

        return [
            'markets_evaluated' => $evaluated,
            'crowd_correct'     => $correct,
            'crowd_accuracy'    => $evaluated > 0 ? round(($correct / $evaluated) * 100, 1) : null,
            'biggest_hit'       => $biggestHit,
            'biggest_miss'      => $biggestMiss,
        ];
    }

    /**
     * @return array{headline: string, detail: string, tags: array<int, string>}
     */
    private function buildMatchNarrative(Fixture $fixture): array
    {
        $home = (int) ($fixture->goals_home ?? 0);
        $away = (int) ($fixture->goals_away ?? 0);
        $total = $home + $away;
        $margin = abs($home - $away);

        $winner = $home > $away
            ? $fixture->homeTeam->name
            : ($away > $home ? $fixture->awayTeam->name : null);

        if ($winner === null) {
            $headline = "{$fixture->homeTeam->name} and {$fixture->awayTeam->name} share the spoils";
        } elseif ($margin >= 3) {
            $headline = "{$winner} run out comfortable winners";
        } elseif ($margin === 1) {
            $headline = "{$winner} edge a tight one";
        } else {
            $headline = "{$winner} take the three points";
        }

        $detail = match (true) {
            $home === 0 && $away === 0 => 'A goalless draw — the crowd had plenty to chew on, but nothing went in.',
            $total >= 5                => "A {$home}-{$away} thriller with {$total} goals — the crowd's over/under calls had real drama.",
            $home === 0 || $away === 0 => "Clean sheet at one end — a {$home}-{$away} scoreline that rewarded the defensively sharp.",
            $margin === 1              => "Tight {$home}-{$away} — one swing goal decided the outcome.",
            default                    => "Final score {$home}-{$away}.",
        };

        $tags = [];
        if ($total >= 5) $tags[] = 'High-scoring';
        if ($total === 0) $tags[] = 'Goalless';
        if ($home === 0 || $away === 0) $tags[] = 'Clean sheet';
        if ($margin >= 3) $tags[] = 'Blowout';
        if ($margin === 1) $tags[] = 'Close match';
        if ($home > 0 && $away > 0) $tags[] = 'Both teams scored';

        return [
            'headline' => $headline,
            'detail'   => $detail,
            'tags'     => $tags,
        ];
    }
}