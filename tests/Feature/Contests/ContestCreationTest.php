<?php

namespace Tests\Feature\Contests;

use App\Models\Betslip;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\League;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContestCreationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_host_can_create_a_private_contest(): void
    {
        $host = $this->makeUser('Denis Wanjohi');

        $contest = Contest::create([
            'host_id'     => $host->id,
            'name'        => 'Sunday Crew',
            'description' => 'Weekly five-pick challenge among friends.',
            'visibility'  => 'private',
            'status'      => 'open',
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(4),
            'ends_at'           => now()->addDays(5),
        ]);

        $this->assertNotNull($contest->uuid);
        $this->assertMatchesRegularExpression(
            '/^CNT-[A-Z0-9]{6}$/',
            $contest->uuid
        );
        $this->assertSame($host->id, $contest->host_id);
        $this->assertSame('private', $contest->visibility);
        $this->assertSame('open', $contest->status);
    }

    public function test_uuid_is_generated_on_creation(): void
    {
        $host = $this->makeUser('Host');

        $contest = Contest::create([
            'host_id'     => $host->id,
            'name'        => 'Test',
            'visibility'  => 'private',
            'status'      => 'open',
            'entry_deadline_at' => now()->addDays(1),
            'starts_at'         => now()->addDays(2),
            'ends_at'           => now()->addDays(3),
        ]);

        $this->assertNotEmpty($contest->uuid);
    }

    public function test_uuids_are_unique(): void
    {
        $host = $this->makeUser('Host');
        $uuids = [];

        for ($i = 0; $i < 10; $i++) {
            $contest = Contest::create([
                'host_id'     => $host->id,
                'name'        => "Contest {$i}",
                'visibility'  => 'private',
                'status'      => 'open',
                'entry_deadline_at' => now()->addDays(1),
                'starts_at'         => now()->addDays(2),
                'ends_at'           => now()->addDays(3),
            ]);

            $this->assertNotContains($contest->uuid, $uuids);
            $uuids[] = $contest->uuid;
        }
    }

    public function test_host_can_add_legs(): void
    {
        $host = $this->makeUser('Host');
        $contest = $this->makeContest($host);

        $fixture = $this->makeFixture();
        $market = $this->makeMarket(id: 1);

        $leg = ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => $market->id,
            'status'     => 'pending',
        ]);

        $this->assertSame($contest->id, $leg->contest_id);
        $this->assertSame($fixture->id, $leg->fixture_id);
        $this->assertCount(1, $contest->fresh()->legs);
    }

    public function test_user_can_join_a_private_contest(): void
    {
        $host   = $this->makeUser('Host');
        $joiner = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $this->assertSame('pending', $entry->status);
        $this->assertCount(1, $contest->fresh()->entries);
    }

    public function test_user_cannot_join_a_contest_twice(): void
    {
        $host   = $this->makeUser('Host');
        $joiner = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);
    }

    public function test_user_can_submit_picks_for_each_leg(): void
    {
        $host   = $this->makeUser('Host');
        $joiner = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        $fixture = $this->makeFixture();
        $market = $this->makeMarket(id: 1);

        $leg = ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => $market->id,
            'status'     => 'pending',
        ]);

        $pick = ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Home',
            'odds_at_pick'     => 2.10,
            'status'           => 'pending',
        ]);

        $this->assertSame('Home', $pick->selection);
        $this->assertSame(2.10, (float) $pick->odds_at_pick);
        $this->assertCount(1, $entry->fresh()->picks);
    }

    public function test_user_cannot_pick_the_same_leg_twice(): void
    {
        $host   = $this->makeUser('Host');
        $joiner = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        $fixture = $this->makeFixture();
        $market = $this->makeMarket(id: 1);

        $leg = ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => $market->id,
            'status'     => 'pending',
        ]);

        ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Home',
            'odds_at_pick'     => 2.10,
            'status'           => 'pending',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Away',
            'odds_at_pick'     => 3.50,
            'status'           => 'pending',
        ]);
    }

    // ------------------ helpers ------------------

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeContest(User $host): Contest
    {
        return Contest::create([
            'host_id'     => $host->id,
            'name'        => 'Test Contest',
            'visibility'  => 'private',
            'status'      => 'open',
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(4),
            'ends_at'           => now()->addDays(5),
        ]);
    }

    private function makeFixture(): Fixture
    {
        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home Team', 900001);
        $away   = $this->makeTeam('Away Team', 900002);

        return Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 99999),
            'date'         => now()->addDays(4)->toDateTimeString(),
            'timestamp'    => now()->addDays(4)->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
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

    private function makeMarket(int $id = 1, string $name = 'Match Winner'): Market
    {
        return Market::firstOrCreate(
            ['id' => $id],
            ['name' => $name]
        );
    }
}