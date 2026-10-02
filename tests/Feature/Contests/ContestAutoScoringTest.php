<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use App\Services\ContestLegResolver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContestAutoScoringTest extends TestCase
{
    use DatabaseMigrations;

    public function test_resolving_one_leg_leaves_contest_open(): void
    {
        $s = $this->scenario();

        $oddA = Odd::where('fixture_id', $s['legs'][0]->fixture_id)
            ->where('market_id', $s['legs'][0]->market_id)
            ->where('value', 'Home')
            ->firstOrFail();
        $oddA->update(['status' => 'won']);

        app(ContestLegResolver::class)->resolveMany([$oddA]);
        app(ContestLegResolver::class)->drainPendingScores();

        $this->assertSame('open', $s['contest']->fresh()->status);
        $this->assertSame('won', $s['legs'][0]->fresh()->status);
        $this->assertSame('Home', $s['legs'][0]->fresh()->result_selection);
        $this->assertSame('pending', $s['legs'][1]->fresh()->status);
    }

    public function test_resolving_last_leg_scores_contest_automatically(): void
    {
        $s = $this->scenario();

        // Submit picks: Home for leg 1, Over 2.5 for leg 2.
        $this->submitPicks($s['entry'], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        // Resolve leg 1.
        $oddA = Odd::where('fixture_id', $s['legs'][0]->fixture_id)
            ->where('market_id', $s['legs'][0]->market_id)
            ->where('value', 'Home')
            ->firstOrFail();
        $oddA->update(['status' => 'won']);

        $resolver = app(ContestLegResolver::class);
        $resolver->resolveMany([$oddA]);
        $resolver->drainPendingScores();

        $this->assertSame('open', $s['contest']->fresh()->status);

        // Resolve leg 2.
        $oddB = Odd::where('fixture_id', $s['legs'][1]->fixture_id)
            ->where('market_id', $s['legs'][1]->market_id)
            ->where('value', 'Over 2.5')
            ->firstOrFail();
        $oddB->update(['status' => 'won']);

        $resolver->resolveMany([$oddB]);
        $resolver->drainPendingScores();

        // Contest should now be settled and entry scored.
        $contest = $s['contest']->fresh();
        $entry   = $s['entry']->fresh();

        $this->assertSame('settled', $contest->status);
        $this->assertSame(2, $entry->score_correct);
        $this->assertSame('4.35', $entry->score_units);
        $this->assertSame(1, $entry->rank_final);
    }

    public function test_already_resolved_leg_is_not_overwritten(): void
    {
        $s = $this->scenario();

        // Pre-resolve leg 1 to won.
        $s['legs'][0]->update([
            'status'           => 'won',
            'result_selection' => 'Home',
            'resolved_at'      => now()->subMinute(),
        ]);
        $originalResolvedAt = $s['legs'][0]->fresh()->resolved_at;

        // Now an odd flips — but it points at the same fixture/market
        // which already resolved. Resolver should skip it.
        $odd = Odd::where('fixture_id', $s['legs'][0]->fixture_id)
            ->where('market_id', $s['legs'][0]->market_id)
            ->where('value', 'Home')
            ->firstOrFail();
        $odd->update(['status' => 'won']);

        app(ContestLegResolver::class)->resolveMany([$odd]);

        $this->assertSame(
            $originalResolvedAt->toDateTimeString(),
            $s['legs'][0]->fresh()->resolved_at->toDateTimeString(),
        );
    }

    // ------------------ helpers ------------------

    private function scenario(): array
    {
        $host = $this->makeUser('Host');
        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Auto-scoring Test',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at'         => now()->addHours(2),
            'ends_at'           => now()->addHours(4),
        ]);

        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home FC', 900001);
        $away   = $this->makeTeam('Away FC', 900002);

        $fx1 = $this->makeFixture($league, $home, $away);
        $fx2 = $this->makeFixture($league, $home, $away);

        $market1 = $this->makeMarket(1, 'Match Winner');
        $market2 = $this->makeMarket(2, 'Over/Under');

        $legs = [
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx1->id,
                'market_id'  => $market1->id,
                'status'     => 'pending',
            ]),
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx2->id,
                'market_id'  => $market2->id,
                'status'     => 'pending',
            ]),
        ];

        $user  = $this->makeUser('Joiner');
        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        return [
            'host'    => $host,
            'user'    => $user,
            'contest' => $contest,
            'entry'   => $entry,
            'legs'    => $legs,
        ];
    }

    private function submitPicks(ContestEntry $entry, array $picks): void
    {
        foreach ($picks as [$leg, $selection]) {
            $odd = Odd::where('fixture_id', $leg->fixture_id)
                ->where('market_id', $leg->market_id)
                ->where('value', $selection)
                ->firstOrFail();

            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id'   => $leg->id,
                'selection'        => $selection,
                'odds_at_pick'     => $odd->odd,
                'status'           => 'pending',
            ]);
        }
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeLeague(): League
    {
        return League::firstOrCreate(
            ['id_on_api' => 900100],
            [
                'sport_id'   => 1,
                'name'       => 'Test League',
                'country_id' => 1,
                'country'    => 'Test Country',
                'season'     => 2026,
            ]
        );
    }

    private function makeTeam(string $name, int $idOnApi): Team
    {
        return Team::firstOrCreate(
            ['id_on_api' => $idOnApi],
            ['name' => $name]
        );
    }

    private function makeFixture(League $league, Team $home, Team $away): Fixture
    {
        $fixture = Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 99999),
            'date'         => now()->addHours(2)->toDateTimeString(),
            'timestamp'    => now()->addHours(2)->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home',      'odd' => 2.50, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away',      'odd' => 3.20, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Over 2.5',  'odd' => 1.85, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Under 2.5', 'odd' => 2.05, 'status' => 'pending']);

        return $fixture;
    }

    private function makeMarket(int $id, string $name): Market
    {
        return Market::firstOrCreate(['id' => $id], ['name' => $name]);
    }
}