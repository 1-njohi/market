<?php

namespace Tests\Unit\Services\Jackpot;

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
use App\Services\Jackpot\JackpotDistributionService;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JackpotDistributionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39,
            'sport_id' => 1,
            'name' => 'Premier League',
            'country_id' => 1,
            'country' => 'England',
            'season' => 2026,
        ]);
    }

    private function service(): JackpotDistributionService
    {
        return app(JackpotDistributionService::class);
    }

    private function contest(): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => 'Jackpot',
            'visibility' => 'public',
            'type' => 'jackpot',
            'status' => 'open',
            'locks_at' => now()->addDay(),
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
    }
    private function leg(Contest $contest): ContestLeg
    {
        $home = Team::firstOrCreate(['id_on_api' => 900000 + random_int(1, 99999)], ['name' => 'H']);
        $away = Team::firstOrCreate(['id_on_api' => 910000 + random_int(1, 99999)], ['name' => 'A']);

        $fx = Fixture::create([
            'id_on_api' => 900000 + random_int(1, 999999),
            'date' => now()->addDay(),
            'timestamp' => now()->addDay()->timestamp,
            'status_short' => 'NS',
            'league_id' => 39,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        // Seed the three standard options so the distribution includes 0% rows.
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.00, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Draw', 'odd' => 3.00, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 4.00, 'status' => 'pending']);

        return ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fx->id,
            'market_id' => 1,
            'status' => 'pending',
        ]);
    }

    private function entry(User $user, Contest $contest): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'joined_at' => now(),
        ]);
    }

    private function pick(ContestEntry $entry, ContestLeg $leg, string $selection, bool $revealed = true): void
    {
        ContestPick::withoutMatchdayLock(fn() => ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id' => $leg->id,
            'selection' => $selection,
            'odds_at_pick' => 2.00,
            'status' => 'pending',
            'revealed_at' => $revealed ? now() : null,
        ]));
    }

    public function test_returns_empty_when_no_picks(): void
    {
        $contest = $this->contest();
        $leg = $this->leg($contest);
        $viewer = User::factory()->create();
        $this->entry($viewer, $contest);

        $rows = $this->service()->distributionFor($contest, $viewer);

        $this->assertSame([], $rows);
    }

    public function test_computes_percentages_per_leg(): void
    {
        $contest = $this->contest();
        $leg = $this->leg($contest);
        $viewer = User::factory()->create();

        $this->entry($viewer, $contest);
        $this->entry(User::factory()->create(), $contest);
        $this->entry(User::factory()->create(), $contest);
        $this->entry(User::factory()->create(), $contest);

        foreach (ContestEntry::all() as $i => $e) {
            // 3 pick Home, 1 picks Draw. Viewer picks Home.
            $this->pick($e, $leg, $i < 3 ? 'Home' : 'Draw');
        }

        $rows = $this->service()->distributionFor($contest, $viewer);

        $this->assertArrayHasKey($leg->id, $rows);
        $this->assertSame(75, $rows[$leg->id]['Home']);
        $this->assertSame(25, $rows[$leg->id]['Draw']);
        $this->assertSame(0, $rows[$leg->id]['Away']);
    }

    public function test_hides_legs_viewer_has_not_picked(): void
    {
        $contest = $this->contest();
        $legA = $this->leg($contest);
        $legB = $this->leg($contest);
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $eViewer = $this->entry($viewer, $contest);
        $eOther = $this->entry($other, $contest);

        // Viewer picked legA only. Other picked both.
        $this->pick($eViewer, $legA, 'Home');
        $this->pick($eOther, $legA, 'Home');
        $this->pick($eOther, $legB, 'Away');

        $rows = $this->service()->distributionFor($contest, $viewer);

        $this->assertArrayHasKey($legA->id, $rows);
        $this->assertArrayNotHasKey($legB->id, $rows);
    }

    public function test_ignores_pending_entries(): void
    {
        $contest = $this->contest();
        $leg = $this->leg($contest);
        $viewer = User::factory()->create();
        $accepted = User::factory()->create();
        $pending = User::factory()->create();

        $eViewer = $this->entry($viewer, $contest);
        $eAccepted = $this->entry($accepted, $contest);
        $ePending = $this->entry($pending, $contest);
        $ePending->update(['status' => 'pending']);

        $this->pick($eViewer, $leg, 'Home');
        $this->pick($eAccepted, $leg, 'Home');
        $this->pick($ePending, $leg, 'Draw');   // must be ignored

        $rows = $this->service()->distributionFor($contest, $viewer);

        $this->assertSame(100, $rows[$leg->id]['Home']);
        $this->assertSame(0, $rows[$leg->id]['Draw']);
    }

    public function test_consensus_score_counts_legs_where_majority_was_correct(): void
    {
        $contest = $this->contest();
        $legA = $this->leg($contest);
        $legB = $this->leg($contest);

        // LegA: 2 Home, 1 Draw → majority Home. Result: Home won.
        // LegB: 2 Draw, 1 Home → majority Draw. Result: Home won (majority wrong).
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        $e1 = $this->entry($u1, $contest);
        $e2 = $this->entry($u2, $contest);
        $e3 = $this->entry($u3, $contest);

        $this->pick($e1, $legA, 'Home');
        $this->pick($e2, $legA, 'Home');
        $this->pick($e3, $legA, 'Draw');

        $this->pick($e1, $legB, 'Draw');
        $this->pick($e2, $legB, 'Draw');
        $this->pick($e3, $legB, 'Home');

        $legA->update(['status' => 'won', 'result_selections' => ['Home']]);
        $legB->update(['status' => 'won', 'result_selections' => ['Home']]);

        $this->assertSame(1, $this->service()->consensusScore($contest->fresh()));
    }
}