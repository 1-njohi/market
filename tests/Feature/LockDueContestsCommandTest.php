<?php

namespace Tests\Feature\Console;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LockDueContestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_contest_with_future_deadline_stays_open(): void
    {
        $contest = $this->makeContest(['entry_deadline_at' => now()->addHour()]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('open', $contest->fresh()->status);
    }

    public function test_open_contest_with_past_deadline_becomes_locked(): void
    {
        $contest = $this->makeContest(['entry_deadline_at' => now()->subMinute()]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_boundary_deadline_exactly_now_locks(): void
    {
        // freezeTime() makes every now() call within the test — including
        // the one inside the command — return the same instant, so the
        // deadline-equal-to-now case is deterministic.
        $this->freezeTime();

        $contest = $this->makeContest(['entry_deadline_at' => now()]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_already_locked_contest_is_not_touched(): void
    {
        $contest = $this->makeContest([
            'status' => 'locked',
            'entry_deadline_at' => now()->subHour(),
        ]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_underway_contest_is_not_touched(): void
    {
        $contest = $this->makeContest([
            'status' => 'underway',
            'entry_deadline_at' => now()->subHour(),
        ]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);
    }

    public function test_settled_contest_is_not_touched(): void
    {
        $contest = $this->makeContest([
            'status' => 'settled',
            'entry_deadline_at' => now()->subHour(),
        ]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('settled', $contest->fresh()->status);
    }

    public function test_multiple_due_contests_are_all_locked(): void
    {
        $a = $this->makeContest(['entry_deadline_at' => now()->subMinutes(5)]);
        $b = $this->makeContest(['entry_deadline_at' => now()->subMinute()]);
        $c = $this->makeContest(['entry_deadline_at' => now()->addMinute()]); // not due

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $a->fresh()->status);
        $this->assertSame('locked', $b->fresh()->status);
        $this->assertSame('open', $c->fresh()->status);
    }

    public function test_running_twice_produces_same_result_as_running_once(): void
    {
        $contest = $this->makeContest(['entry_deadline_at' => now()->subMinute()]);

        $this->artisan('contests:lock-due')->assertSuccessful();
        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_no_op_when_no_contests_are_due(): void
    {
        $this->makeContest(['entry_deadline_at' => now()->addDay()]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertDatabaseMissing('contests', ['status' => 'locked']);
    }

    public function test_open_contest_without_legs_still_locks(): void
    {
        // Locking is deadline-driven, not leg-driven. A contest with no
        // legs is unusual but valid — it locks when the deadline passes.
        $contest = $this->makeContest(['entry_deadline_at' => now()->subMinute()]);

        $this->artisan('contests:lock-due')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeContest(array $attributes = []): Contest
    {
        $host = User::factory()->create();

        return Contest::withoutHostGuard(fn () => Contest::forceCreate(array_merge([
            'uuid' => (string) Str::uuid(),
            'host_id' => $host->id,
            'name' => 'Lock Test',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ], $attributes)));
    }
}