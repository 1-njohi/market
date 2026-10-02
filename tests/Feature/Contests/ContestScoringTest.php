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
use App\Services\ContestScoringService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContestScoringTest extends TestCase
{
    use DatabaseMigrations;

    public function test_correct_picks_score_one_and_snapshot_odds(): void
    {
        $s = $this->scenario();

        // Both legs resolve: Home wins leg 1, Over 2.5 wins leg 2.
        $this->resolveLeg($s['legs'][0], 'Home');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');

        // User picked Home (2.50) and Over 2.5 (1.85) — both correct.
        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        app(ContestScoringService::class)->score($s['contest']);

        $entry = $s['entry']->fresh();

        $this->assertSame(2, $entry->score_correct);
        $this->assertSame('4.35', $entry->score_units); // 2.50 + 1.85
        $this->assertSame(1, $entry->rank_final);
        $this->assertSame('settled', $s['contest']->fresh()->status);
    }

    public function test_incorrect_picks_do_not_contribute_units(): void
    {
        $s = $this->scenario();

        $this->resolveLeg($s['legs'][0], 'Away');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');

        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],       // wrong
            [$s['legs'][1], 'Over 2.5'],   // right
        ]);

        app(ContestScoringService::class)->score($s['contest']);

        $entry = $s['entry']->fresh();

        $this->assertSame(1, $entry->score_correct);
        $this->assertSame('1.85', $entry->score_units); // only the correct pick
    }

    public function test_void_legs_are_excluded_from_scoring(): void
    {
        $s = $this->scenario();

        $this->resolveLeg($s['legs'][0], 'Home');
        $this->voidLeg($s['legs'][1]);

        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        app(ContestScoringService::class)->score($s['contest']);

        $entry = $s['entry']->fresh();

        // Void leg contributes neither to correct count nor units.
        $this->assertSame(1, $entry->score_correct);
        $this->assertSame('2.50', $entry->score_units);
    }

    public function test_rankings_sort_by_correct_then_units(): void
    {
        $s = $this->scenario(extraEntries: 2);

        $this->resolveLeg($s['legs'][0], 'Home');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');

        // Entry A: 1 correct, 2.50 units (Home only).
        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Under 2.5'],
        ], entryIndex: 0);

        // Entry B: 2 correct, 4.35 units (Home + Over 2.5).
        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ], entryIndex: 1);

        // Entry C: 0 correct.
        $this->submitPicks($s, [
            [$s['legs'][0], 'Away'],
            [$s['legs'][1], 'Under 2.5'],
        ], entryIndex: 2);

        app(ContestScoringService::class)->score($s['contest']);

        $entries = ContestEntry::where('contest_id', $s['contest']->id)
            ->orderBy('rank_final')
            ->get();

        $this->assertSame(1, $entries[0]->rank_final);  // B — 2 correct
        $this->assertSame(2, $entries[1]->rank_final);  // A — 1 correct
        $this->assertSame(3, $entries[2]->rank_final);  // C — 0 correct
    }

    public function test_scoring_is_idempotent(): void
    {
        $s = $this->scenario();

        $this->resolveLeg($s['legs'][0], 'Home');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');
        $this->submitPicks($s, [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        $service = app(ContestScoringService::class);
        $service->score($s['contest']);
        $service->score($s['contest']->fresh());

        $entry = $s['entry']->fresh();
        $this->assertSame(2, $entry->score_correct);
        $this->assertSame(1, $entry->rank_final);
    }

    public function test_cannot_score_while_legs_are_pending(): void
    {
        $s = $this->scenario();

        // Only one leg resolved.
        $this->resolveLeg($s['legs'][0], 'Home');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/unresolved/i');

        app(ContestScoringService::class)->score($s['contest']);
    }

    // ------------------ helpers ------------------

    private function scenario(int $extraEntries = 0): array
    {
        $host = $this->makeUser('Host');
        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Test Contest',
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

        // Main entry (index 0).
        $user  = $this->makeUser('Joiner');
        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        // Extra entries.
        $extra = [];
        for ($i = 0; $i < $extraEntries; $i++) {
            $u = $this->makeUser("Joiner {$i}");
            $extra[] = ContestEntry::create([
                'contest_id' => $contest->id,
                'user_id'    => $u->id,
                'status'     => 'accepted',
                'joined_at'  => now(),
            ]);
        }

        return [
            'host'    => $host,
            'user'    => $user,
            'contest' => $contest,
            'entry'   => $entry,
            'entries' => array_merge([$entry], $extra),
            'legs'    => $legs,
        ];
    }

    private function submitPicks(array $s, array $picks, int $entryIndex = 0): void
    {
        $entry = $s['entries'][$entryIndex];

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

    private function resolveLeg(ContestLeg $leg, string $result): void
    {
        $leg->update([
            'status'           => 'won',
            'result_selection' => $result,
            'resolved_at'      => now(),
        ]);
    }

    private function voidLeg(ContestLeg $leg): void
    {
        $leg->update([
            'status'      => 'void',
            'resolved_at' => now(),
        ]);
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