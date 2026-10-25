<?php

namespace Tests\Feature\Matchday;

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
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PickSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));

        League::firstOrCreate(
            ['id' => 39],
            [
                'id_on_api'  => 39,
                'sport_id'   => 1,
                'name'       => 'Premier League',
                'country_id' => 1,
                'country'    => 'England',
                'season'     => 2026,
            ],
        );
    }

    private function matchdayContest(array $overrides = []): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create(array_merge([
            'host_id'           => $host->id,
            'name'              => 'MD1',
            'visibility'        => 'public',
            'status'            => 'open',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => 1,
            'locks_at'          => now()->addHours(2),
            'entry_deadline_at' => now()->addHours(2),
            'starts_at'         => now()->addHours(3),
            'ends_at'           => now()->addHours(5),
        ], $overrides));
    }

    private function leg(Contest $contest, string $value = 'Home'): ContestLeg
    {
        $home = Team::firstOrCreate(['id_on_api' => 900000 + random_int(1, 99999)], ['name' => 'H']);
        $away = Team::firstOrCreate(['id_on_api' => 910000 + random_int(1, 99999)], ['name' => 'A']);

        $fixture = Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 999999),
            'date'         => now()->addHours(3),
            'timestamp'    => now()->addHours(3)->timestamp,
            'status_short' => 'NS',
            'league_id'    => 39,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.50, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 3.20, 'status' => 'pending']);

        return ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'status'     => 'pending',
        ]);
    }

    private function entry(User $user, Contest $contest, string $status = 'accepted'): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => $status,
            'joined_at'  => now(),
        ]);
    }

    private function pickPayload(array $legs, string $selection = 'Home'): array
    {
        return [
            'picks' => array_map(
                fn (ContestLeg $l) => ['leg_id' => $l->id, 'selection' => $selection],
                $legs,
            ),
        ];
    }

    public function test_user_can_submit_pick_for_leg_on_matchday_contest_before_lock(): void
    {
        $user    = User::factory()->create();
        $contest = $this->matchdayContest();
        $entry   = $this->entry($user, $contest);
        $leg     = $this->leg($contest);

        $response = $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg]),
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('contest_picks', [
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Home',
        ]);
    }

    public function test_user_can_update_pick_before_lock(): void
    {
        $user    = User::factory()->create();
        $contest = $this->matchdayContest();
        $entry   = $this->entry($user, $contest);
        $leg     = $this->leg($contest);

        // Seed an existing pick.
        ContestPick::create([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Home',
            'odds_at_pick'     => 2.50,
            'status'           => 'pending',
        ]);

        $response = $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg], 'Away'),
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(
            'Away',
            ContestPick::where('contest_entry_id', $entry->id)
                ->where('contest_leg_id', $leg->id)
                ->value('selection')
        );
    }

    public function test_pick_submission_rejected_at_lock_time(): void
    {
        $user    = User::factory()->create();
        $contest = $this->matchdayContest(['locks_at' => now()->addMinutes(5)]);
        $entry   = $this->entry($user, $contest);
        $leg     = $this->leg($contest);

        // Advance to exactly locks_at.
        $this->travelTo($contest->locks_at);

        $response = $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg]),
        );

        $response->assertSessionHasErrors('picks');
        $this->assertDatabaseMissing('contest_picks', [
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
        ]);
    }

    public function test_pick_submission_rejected_after_lock(): void
    {
        $user    = User::factory()->create();
        $contest = $this->matchdayContest(['locks_at' => now()->subHour()]);
        $entry   = $this->entry($user, $contest);
        $leg     = $this->leg($contest);

        $response = $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg]),
        );

        $response->assertSessionHasErrors('picks');
        $response->assertSessionHas('errors', function ($errors) {
            return str_contains($errors->first('picks'), 'matchday');
        });

        $this->assertDatabaseMissing('contest_picks', [
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
        ]);
    }

    public function test_pick_submission_rejected_for_non_member(): void
    {
        $outsider = User::factory()->create();
        $contest  = $this->matchdayContest();
        $leg      = $this->leg($contest);

        $response = $this->actingAs($outsider)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg]),
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('contest_picks', ['contest_leg_id' => $leg->id]);
    }

    public function test_pick_submission_rejected_for_pending_entry(): void
    {
        $user    = User::factory()->create();
        $contest = $this->matchdayContest();
        $this->entry($user, $contest, 'pending');
        $leg     = $this->leg($contest);

        $response = $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload([$leg]),
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('contest_picks', ['contest_leg_id' => $leg->id]);
    }

    public function test_user_can_submit_partial_picks_and_score_zero_for_missing_legs(): void
    {
        Notification::fake();

        $user    = User::factory()->create();
        $contest = $this->matchdayContest();
        $entry   = $this->entry($user, $contest);

        // Five legs, three with matching picks.
        $legs = collect(range(1, 5))->map(fn () => $this->leg($contest));
        $submitted = $legs->take(3)->all();

        $this->actingAs($user)->post(
            route('contests.picks.store', ['uuid' => $contest->uuid]),
            $this->pickPayload($submitted),
        )->assertSessionHasNoErrors();

        $this->assertSame(3, ContestPick::where('contest_entry_id', $entry->id)->count());

        // Settle every leg. The 3 submitted picks win; the 2 missing legs
        // contribute nothing (the scorer iterates picks, not legs).
        foreach ($contest->legs as $leg) {
            $leg->update(['status' => 'won', 'result_selections' => ['Home']]);
        }

        app(ContestScoringService::class)->scoreFinal($contest->fresh());

        $this->assertSame(3, (int) $entry->fresh()->score_correct);
    }
}