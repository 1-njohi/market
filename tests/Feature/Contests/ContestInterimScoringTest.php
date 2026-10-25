<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\User;
use App\Notifications\ContestSettledNotification;
use App\Services\ContestScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContestInterimScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_interim_scoring_writes_scores_from_resolved_legs_only(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $entry = $s['entry']->fresh();

        // Only the resolved leg contributes — one pick was Home (won).
        $this->assertSame(1, $entry->score_correct);
        $this->assertSame('1.80', $entry->score_units);
    }

    public function test_interim_scoring_leaves_picks_on_pending_legs_pending(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $picks = ContestPick::where('contest_entry_id', $s['entry']->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('correct', $picks[0]->status); // Home vs resolved Home leg
        $this->assertSame('pending', $picks[1]->status); // Over 2.5 vs pending leg
    }

    public function test_interim_scoring_writes_rank_current_and_does_not_touch_rank_previous_when_null(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        $this->assertNull($s['entry']->rank_current);
        $this->assertNull($s['entry']->rank_previous);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $entry = $s['entry']->fresh();
        $this->assertSame(1, $entry->rank_current);
        $this->assertNull($entry->rank_previous);
    }

    public function test_interim_scoring_preserves_old_rank_current_into_rank_previous(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        // Seed a previous state: this entry was rank 3 before the pass.
        $s['entry']->update(['rank_current' => 3]);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $entry = $s['entry']->fresh();
        $this->assertSame(3, $entry->rank_previous);
        $this->assertSame(1, $entry->rank_current);
    }

    public function test_interim_scoring_does_not_notify(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        Notification::assertNothingSent();
    }

    public function test_interim_scoring_does_not_flip_contest_or_entry_status(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $this->assertSame('underway', $s['contest']->fresh()->status);
        $this->assertNull($s['entry']->fresh()->settled_at);
    }

    public function test_interim_scoring_is_idempotent(): void
    {
        $s = $this->scenario(resolvedSelections: ['Home'], pendingSelections: ['Over 2.5']);

        $service = app(ContestScoringService::class);
        $service->scoreInterim($s['contest']);
        $service->scoreInterim($s['contest']->fresh());

        $entry = $s['entry']->fresh();
        $this->assertSame(1, $entry->score_correct);
        $this->assertSame(1, $entry->rank_current);
        // Second pass: previous rank_current (1) rolls into rank_previous.
        $this->assertSame(1, $entry->rank_previous);
    }

    public function test_interim_scoring_ranks_entries_correctly_across_mixed_resolution(): void
    {
        // Alice picks a winner; Bob picks a loser. Both have a pending leg.
        [$contest, $fixtureA, $fixtureB] = $this->baseContest();

        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceEntry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $alice->id,
            'status' => 'accepted',
            'joined_at' => now()->subMinutes(10),
        ]);
        $bobEntry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $bob->id,
            'status' => 'accepted',
            'joined_at' => now()->subMinutes(5),
        ]);

        $legA = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixtureA->id,
            'market_id' => 1,
            'status' => 'pending',
        ]);
        $legB = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixtureB->id,
            'market_id' => 5,
            'status' => 'pending',
        ]);

        ContestPick::forceCreate([
            'contest_entry_id' => $aliceEntry->id,
            'contest_leg_id' => $legA->id,
            'selection' => 'Home',
            'odds_at_pick' => 1.80,
            'status' => 'pending',
        ]);
        ContestPick::forceCreate([
            'contest_entry_id' => $aliceEntry->id,
            'contest_leg_id' => $legB->id,
            'selection' => 'Over 2.5',
            'odds_at_pick' => 1.90,
            'status' => 'pending',
        ]);
        ContestPick::forceCreate([
            'contest_entry_id' => $bobEntry->id,
            'contest_leg_id' => $legA->id,
            'selection' => 'Away',
            'odds_at_pick' => 4.20,
            'status' => 'pending',
        ]);
        ContestPick::forceCreate([
            'contest_entry_id' => $bobEntry->id,
            'contest_leg_id' => $legB->id,
            'selection' => 'Over 2.5',
            'odds_at_pick' => 1.90,
            'status' => 'pending',
        ]);

        // Fixture A resolves: Home won.
        $legA->update([
            'status' => 'won',
            'result_selections' => ['Home'],
            'resolved_at' => now(),
        ]);

        app(ContestScoringService::class)->scoreInterim($contest);

        $aliceEntry->refresh();
        $bobEntry->refresh();

        $this->assertSame(1, $aliceEntry->score_correct);
        $this->assertSame('1.80', $aliceEntry->score_units);
        $this->assertSame(1, $aliceEntry->rank_current);

        $this->assertSame(0, $bobEntry->score_correct);
        $this->assertSame('0.00', $bobEntry->score_units);
        $this->assertSame(2, $bobEntry->rank_current);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{contest: Contest, fixtureA: Fixture, fixtureB: Fixture}
     */
    private function baseContest(): array
    {
        $host = User::factory()->create();

        $contest = Contest::withoutHostGuard(fn() => Contest::forceCreate([
            'uuid' => (string) Str::uuid(),
            'host_id' => $host->id,
            'name' => 'Interim Test',
            'visibility' => 'private',
            'status' => 'underway',
            'entry_deadline_at' => now()->subHours(2),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]));

        $fixtureA = $this->makeFixture();
        $fixtureB = $this->makeFixture();

        return [$contest, $fixtureA, $fixtureB];
    }

    private function makeFixture(): Fixture
    {
        static $counter = 990000;

        return Fixture::forceCreate([
            'id_on_api' => $counter++,
            'date' => now(),
            'timestamp' => now()->timestamp,
            'status_short' => 'FT',
            'status_long' => 'Match Finished',
            'home_team_id' => 100,
            'away_team_id' => 200,
            'home_winner' => false,
            'settled' => false,
        ]);
    }

    /**
     * One-entry scenario with one resolved leg and one pending leg.
     *
     * @param  array<int, string>  $resolvedSelections
     * @param  array<int, string>  $pendingSelections
     * @return array{contest: Contest, entry: ContestEntry, player: User}
     */
    private function scenario(
        array $resolvedSelections,
        array $pendingSelections,
    ): array {
        [$contest, $fixtureA, $fixtureB] = $this->baseContest();

        $player = User::factory()->create();

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $player->id,
            'status' => 'accepted',
            'joined_at' => now()->subMinutes(5),
        ]);

        $legResolved = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixtureA->id,
            'market_id' => 1,
            'status' => 'won',
            'result_selections' => $resolvedSelections,
            'resolved_at' => now(),
        ]);

        $legPending = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixtureB->id,
            'market_id' => 5,
            'status' => 'pending',
        ]);

        foreach ($resolvedSelections as $selection) {
            ContestPick::forceCreate([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legResolved->id,
                'selection' => $selection,
                'odds_at_pick' => 1.80,
                'status' => 'pending',
            ]);
        }

        foreach ($pendingSelections as $selection) {
            ContestPick::forceCreate([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legPending->id,
                'selection' => $selection,
                'odds_at_pick' => 1.90,
                'status' => 'pending',
            ]);
        }

        return [
            'contest' => $contest,
            'entry' => $entry,
            'player' => $player,
        ];
    }
}