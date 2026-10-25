<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicContestTest extends TestCase
{
    use RefreshDatabase;

    // ────────────── Visibility derivation ─────────────────────────────────

    public function test_admin_host_with_auto_entry_publishes_a_public_contest(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createContestAsHost($admin, autoEntry: true);

        $this->assertSame('public', Contest::latest('id')->first()->visibility);
    }

    public function test_admin_host_without_auto_entry_publishes_a_private_contest(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->createContestAsHost($admin, autoEntry: false);

        $this->assertSame('private', Contest::latest('id')->first()->visibility);
    }

    public function test_non_admin_host_with_auto_entry_publishes_a_private_contest(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->createContestAsHost($user, autoEntry: true);

        $this->assertSame('private', Contest::latest('id')->first()->visibility);
    }

    public function test_non_admin_host_without_auto_entry_publishes_a_private_contest(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->createContestAsHost($user, autoEntry: false);

        $this->assertSame('private', Contest::latest('id')->first()->visibility);
    }

    // ────────────── Welcome-page listing ─────────────────────────────────

    public function test_welcome_page_omits_the_public_contests_section_when_there_are_none(): void
    {
        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page->where('public_contests', [])
        );
    }

    public function test_welcome_page_lists_public_contests_ordered_by_deadline(): void
    {
        $host = User::factory()->create(['is_admin' => true]);

        $later = $this->makeContest($host, 'Later', now()->addHours(6));
        $sooner = $this->makeContest($host, 'Sooner', now()->addHours(2));
        $middle = $this->makeContest($host, 'Middle', now()->addHours(4));

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page
                ->has('public_contests', 3)
                ->where('public_contests.0.name', 'Sooner')
                ->where('public_contests.1.name', 'Middle')
                ->where('public_contests.2.name', 'Later')
        );
    }

    public function test_welcome_page_caps_public_contests_at_six(): void
    {
        $host = User::factory()->create(['is_admin' => true]);

        for ($i = 0; $i < 8; $i++) {
            $this->makeContest($host, "Contest {$i}", now()->addHours($i + 1));
        }

        $response = $this->get('/');

        $response->assertInertia(fn($page) => $page->has('public_contests', 6));
    }

    public function test_welcome_page_excludes_private_contests(): void
    {
        $host = User::factory()->create(['is_admin' => true]);

        $this->makeContest($host, 'Public One', now()->addHours(2), visibility: 'public');
        $this->makeContest($host, 'Private One', now()->addHours(3), visibility: 'private');

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page
                ->has('public_contests', 1)
                ->where('public_contests.0.name', 'Public One')
        );
    }

    public function test_welcome_page_excludes_public_contests_past_their_deadline(): void
    {
        $host = User::factory()->create(['is_admin' => true]);

        $this->makeContest($host, 'Live', now()->addHours(2));
        $this->makeContest($host, 'Expired', now()->subHour());

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page
                ->has('public_contests', 1)
                ->where('public_contests.0.name', 'Live')
        );
    }

    public function test_welcome_page_excludes_public_contests_that_are_not_open(): void
    {
        $host = User::factory()->create(['is_admin' => true]);

        $this->makeContest($host, 'Open One', now()->addHours(2), status: 'open');
        $this->makeContest($host, 'Locked One', now()->addHours(3), status: 'locked');
        $this->makeContest($host, 'Settled One', now()->addHours(4), status: 'settled');

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page
                ->has('public_contests', 1)
                ->where('public_contests.0.name', 'Open One')
        );
    }

    public function test_entries_count_only_counts_accepted_entries(): void
    {
        $host = User::factory()->create(['is_admin' => true]);
        $contest = $this->makeContest($host, 'Counting', now()->addHours(2));

        ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'accepted',
            'joined_at' => now(),
        ]);
        ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'pending',
            'joined_at' => now(),
        ]);
        ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'rejected',
            'joined_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page->where('public_contests.0.entries_count', 1)
        );
    }

    public function test_is_joined_is_true_when_the_viewer_has_an_accepted_entry(): void
    {
        $host = User::factory()->create(['is_admin' => true]);
        $viewer = User::factory()->create();

        $joined = $this->makeContest($host, 'Joined', now()->addHours(2));
        $notJoined = $this->makeContest($host, 'NotJoined', now()->addHours(3));

        ContestEntry::forceCreate([
            'contest_id' => $joined->id,
            'user_id' => $viewer->id,
            'status' => 'accepted',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get('/');

        $response->assertInertia(
            fn($page) => $page
                ->where('public_contests.0.is_joined', true)   // Joined
                ->where('public_contests.1.is_joined', false)  // NotJoined
        );
    }

    public function test_is_joined_is_false_for_a_pending_entry(): void
    {
        $host = User::factory()->create(['is_admin' => true]);
        $viewer = User::factory()->create();
        $contest = $this->makeContest($host, 'Pending', now()->addHours(2));

        ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $viewer->id,
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get('/');

        $response->assertInertia(
            fn($page) => $page->where('public_contests.0.is_joined', false)
        );
    }

    public function test_is_joined_is_false_for_guests(): void
    {
        $host = User::factory()->create(['is_admin' => true]);
        $this->makeContest($host, 'Anon', now()->addHours(2));

        $response = $this->get('/');

        $response->assertInertia(
            fn($page) => $page->where('public_contests.0.is_joined', false)
        );
    }

    // ────────────── Helpers ──────────────────────────────────────────────

    /**
     * Seed a session draft, then hit the real POST /contests endpoint
     * so the controller's visibility derivation runs end to end.
     */
    private function createContestAsHost(User $host, bool $autoEntry): void
    {
        $this->makeEligibleHost($host);

        $legs = $this->makeDraftLegs();

        $this->actingAs($host);
        session(['contest_draft' => $legs]);

        $earliestKickoff = Fixture::whereIn('id', array_column($legs, 'fixture_id'))
            ->min('date');

        $this->post('/contests', [
            'name' => 'Test',
            'description' => null,
            'entry_deadline_at' => \Carbon\CarbonImmutable::parse($earliestKickoff)
                ->subHour()
                ->toDateTimeString(),
            'auto_entry' => $autoEntry,
        ])->assertRedirect();
    }

    /**
     * Five legs, each on a distinct fixture+market — satisfies
     * ContestCreateController::MIN_LEGS and the no-duplicate-pair rule.
     *
     * @return array<int, array{fixture_id:int, market_id:int, selection:string}>
     */
    private function makeDraftLegs(): array
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 900200],
            [
                'sport_id' => 1,
                'name' => 'Visibility Test League',
                'country_id' => 1,
                'country' => 'Test Country',
                'season' => 2026,
            ]
        );

        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $fixture = $this->makeFixture($league, $i);
            $legs[] = [
                'fixture_id' => $fixture->id,
                'market_id' => 1,
                'selection' => 'Home',
            ];
        }

        return $legs;
    }

    private function makeFixture(League $league, int $offset): Fixture
    {
        $home = Team::firstOrCreate(
            ['id_on_api' => 950000 + $offset * 2],
            ['name' => "Home {$offset}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 950001 + $offset * 2],
            ['name' => "Away {$offset}"],
        );

        $fixture = Fixture::forceCreate([
            'id_on_api' => 960000 + $offset,
            'date' => now()->addHours(4 + $offset),
            'timestamp' => now()->addHours(4 + $offset)->timestamp,
            'status_short' => 'NS',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner' => false,
            'settled' => false,
        ]);

        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'value' => 'Home',
            'odd' => 2.50,
            'status' => 'pending',
        ]);
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'value' => 'Away',
            'odd' => 3.20,
            'status' => 'pending',
        ]);

        return $fixture;
    }

    /**
     * Direct contest insert for the Welcome-page tests, where the
     * create-controller flow isn't under test.
     */
    private function makeContest(
        User $host,
        string $name,
        \Carbon\CarbonImmutable $deadline,
        string $visibility = 'public',
        string $status = 'open',
    ): Contest {
        return Contest::withoutHostGuard(fn() => Contest::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'host_id' => $host->id,
            'name' => $name,
            'visibility' => $visibility,
            'auto_entry' => true,
            'status' => $status,
            'entry_deadline_at' => $deadline,
            'starts_at' => $deadline->copy()->addHour(),
            'ends_at' => $deadline->copy()->addHours(3),
        ]));
    }

    private function makeEligibleHost(User $host): void
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 900200],
            [
                'sport_id' => 1,
                'name' => 'Visibility Test League',
                'country_id' => 1,
                'country' => 'Test Country',
                'season' => 2026,
            ]
        );

        // 20 settled picks, 12 won → 60% > 0.55 threshold.
        // Offset starts at 100 to avoid colliding with the draft-fixture
        // offsets (0..4) that makeDraftLegs uses.
        for ($i = 0; $i < 20; $i++) {
            $fixture = $this->makeFixture($league, 100 + $i);

            // Market 100+ so we don't collide with the market-1 odds that
            // makeFixture already inserted for this fixture.
            $odd = Odd::create([
                'fixture_id' => $fixture->id,
                'market_id' => 100 + $i,
                'value' => 'Home',
                'odd' => 2.00,
                'status' => 'pending',
            ]);

            $slip = \App\Models\Betslip::forceCreate([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $host->id,
                'code' => strtoupper(\Illuminate\Support\Str::random(10)),
                'total_odds' => 2.00,
                'price' => 100,
                'status' => 'settled',
                'remaining' => 0,
                'is_winner' => $i < 12,
                'is_sellable' => false,
                'is_delisted' => false,
                'priority_score' => 1,
            ]);

            DB::table('betslip_odd')->insert([
                'betslip_id' => $slip->id,
                'odd_id' => $odd->id,
                'status' => $i < 12 ? 'won' : 'lost',
                'odd_value_at_time' => 2.00,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }
    }
}