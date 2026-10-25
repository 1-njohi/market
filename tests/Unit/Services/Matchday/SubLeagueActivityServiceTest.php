<?php

namespace Tests\Unit\Services\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\SubLeagueActivityService;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubLeagueActivityServiceTest extends TestCase
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

        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
        ]);

        $this->travelTo(Carbon::parse('2026-08-15 15:00:00'));
    }

    private function service(): SubLeagueActivityService
    {
        return app(SubLeagueActivityService::class);
    }

    private function subLeague(): SubLeague
    {
        $owner = User::factory()->create();

        return app(SubLeagueService::class)->create($owner, 'Crew', '2026-27', ['EPL']);
    }

    private function join(User $user, SubLeague $sub, \Carbon\CarbonInterface $at = null): void
    {
        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id' => $user->id,
            'joined_at' => $at ?? now(),
        ]);
    }

    private function lockedMatchday(int $number = 1): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number}",
            'visibility' => 'public',
            'status' => 'underway',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->subHours(2),
            'entry_deadline_at' => now()->subHours(2),
            'starts_at' => now()->subHours(1),
            'ends_at' => now()->addHours(3),
        ]);
    }

    private function openMatchday(int $number = 2): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number}",
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->addDays(3),
            'entry_deadline_at' => now()->addDays(3),
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(4),
        ]);
    }

    private function entry(User $user, Contest $contest): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'score_correct' => 0,
            'joined_at' => now()->subHours(4),
        ]);
    }

    private function pick(ContestEntry $entry, Contest $contest, ?Carbon $at = null): ContestPick
    {
        $leg = ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => \App\Models\Fixture::factory()->create(['league_id' => 39])->id,
            'market_id' => 1,
            'status' => 'pending',
        ]);

        return ContestPick::withoutMatchdayLock(fn() => ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id' => $leg->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'status' => 'pending',
            'created_at' => $at ?? now()->subHour(),
            'updated_at' => $at ?? now()->subHour(),
        ]));
    }

    public function test_returns_empty_when_no_activity(): void
    {
        $sub = $this->subLeague();

        $this->assertSame([], $this->service()->recent($sub));
    }

    public function test_join_events_appear(): void
    {
        $sub = $this->subLeague();
        $user = User::factory()->create(['name' => 'Alice']);

        $this->join($user, $sub, now()->subHours(2));

        $rows = $this->service()->recent($sub);

        $this->assertCount(1, $rows);
        $this->assertSame('join', $rows[0]['type']);
        $this->assertSame('Alice', $rows[0]['actor_name']);
    }

    public function test_pick_events_only_appear_after_lock(): void
    {
        $sub = $this->subLeague();
        $user = User::factory()->create(['name' => 'Alice']);
        $this->join($user, $sub);

        $locked = $this->lockedMatchday(1);
        $open = $this->openMatchday(2);

        $e1 = $this->entry($user, $locked);
        $e2 = $this->entry($user, $open);

        $this->pick($e1, $locked);
        $this->pick($e2, $open);

        $rows = $this->service()->recent($sub);

        $pickRows = array_filter($rows, fn($r) => $r['type'] === 'pick');
        $this->assertCount(1, $pickRows);

        $pickRow = array_values($pickRows)[0];
        $this->assertSame(1, $pickRow['matchday_number']);
    }

    public function test_settlement_events_appear_with_viewers_rank(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $sub = app(SubLeagueService::class)->create($user, 'Crew', '2026-27', ['EPL']);

        $md = $this->lockedMatchday(1); // Name is "MD1"
        $md->update(['status' => 'settled', 'settled_at' => now()->subHour()]);

        $entry = $this->entry($user, $md);
        $entry->update([
            'score_correct' => 5,
            'rank_current' => 3,
            'settled_at' => now()->subHour(),
        ]);

        $rows = $this->service()->recent($sub);

        $this->assertCount(1, $rows);
        $this->assertSame('settlement', $rows[0]['type']);
        $this->assertSame('MD1', $rows[0]['contest_name']); // <-- Changed
        $this->assertSame(3, $rows[0]['rank']);
    }

    public function test_activity_is_scoped_to_members(): void
    {
        $sub = $this->subLeague();
        $member = User::factory()->create(['name' => 'Alice']);
        $outsider = User::factory()->create(['name' => 'Bob']);

        $this->join($member, $sub);

        $md = $this->lockedMatchday(1);

        $this->entry($member, $md);
        $outsiderEntry = $this->entry($outsider, $md);
        $this->pick($outsiderEntry, $md);

        $rows = $this->service()->recent($sub);

        $pickRows = array_filter($rows, fn($r) => $r['type'] === 'pick');
        $this->assertCount(0, $pickRows);

        $joinRows = array_filter($rows, fn($r) => $r['type'] === 'join');
        $this->assertCount(1, $joinRows);
        $this->assertSame('Alice', array_values($joinRows)[0]['actor_name']);
    }

    public function test_results_are_limited_and_ordered_by_timestamp_desc(): void
    {
        $sub = $this->subLeague();

        for ($i = 0; $i < 15; $i++) {
            $u = User::factory()->create(['name' => "User{$i}"]);
            $this->join($u, $sub, now()->subMinutes($i));
        }

        $rows = $this->service()->recent($sub, limit: 10);

        $this->assertCount(10, $rows);
        $this->assertSame('User0', $rows[0]['actor_name']);
        $this->assertSame('User9', $rows[9]['actor_name']);
    }
}