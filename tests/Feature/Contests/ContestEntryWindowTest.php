<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\ContestEntryStatusNotification;
use App\Notifications\ContestJoinRequestedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Guards around the entry window:
 *   - store: reject once the deadline passes, even before the scheduled
 *     lock command fires (≤60s lag)
 *   - accept: host can still approve pending requests while locked, but
 *     not once the contest is underway or settled
 */
class ContestEntryWindowTest extends TestCase
{
    use RefreshDatabase;

    // ────────────── store: deadline enforcement ──────────────────────────

    public function test_user_can_join_an_open_contest_before_its_deadline(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}")
            ->assertRedirect("/contests/join/{$contest->uuid}");

        $this->assertDatabaseHas('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_join_a_locked_contest(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'locked',
            'entry_deadline_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_join_an_underway_contest(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'underway',
            'entry_deadline_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_join_a_settled_contest(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'settled',
            'entry_deadline_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_join_open_contest_whose_deadline_has_passed(): void
    {
        // Defensive: the scheduled command may not have fired yet, but the
        // deadline itself is authoritative. This is the ≤60s gap.
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'entry_deadline_at' => now()->subSecond(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    // ────────────── accept: window enforcement ──────────────────────────

    public function test_host_can_accept_pending_entry_while_contest_is_locked(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'locked',
            'entry_deadline_at' => now()->subMinute(),
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $player->id,
            'status' => 'pending',
            'joined_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept")
            ->assertRedirect();

        $this->assertSame('accepted', $entry->fresh()->status);
    }

    public function test_host_cannot_accept_pending_entry_once_contest_is_underway(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'underway',
            'entry_deadline_at' => now()->subHour(),
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $player->id,
            'status' => 'pending',
            'joined_at' => now()->subHours(2),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_host_cannot_accept_pending_entry_once_contest_is_settled(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'settled',
            'entry_deadline_at' => now()->subDay(),
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $player->id,
            'status' => 'pending',
            'joined_at' => now()->subDay(),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_host_cannot_accept_pending_entry_once_contest_is_cancelled(): void
    {
        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'cancelled',
            'entry_deadline_at' => now()->subDay(),
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $player->id,
            'status' => 'pending',
            'joined_at' => now()->subDay(),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $this->assertSame('pending', $entry->fresh()->status);
    }

    // ────────────── Auto-entry ─────────────────────────────────────────────

    public function test_auto_entry_contest_immediately_accepts_the_joining_user(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => true,
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}")
            ->assertRedirect("/contests/join/{$contest->uuid}");

        $this->assertDatabaseHas('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
        ]);
    }

    public function test_manual_contest_still_creates_a_pending_entry(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => false,
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseHas('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_auto_entry_does_not_bypass_the_deadline(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => true,
            'entry_deadline_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_auto_entry_does_not_bypass_a_locked_contest(): void
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'locked',
            'auto_entry' => true,
            'entry_deadline_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_auto_entry_does_not_apply_to_the_host(): void
    {
        $host = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => true,
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($host)
            ->post("/contests/join/{$contest->uuid}");

        $this->assertDatabaseMissing('contest_entries', [
            'contest_id' => $contest->id,
            'user_id' => $host->id,
        ]);
    }

    public function test_manual_join_notifies_the_host_and_leaves_the_user_pending(): void
    {
        Notification::fake();

        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => false,
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        Notification::assertSentTo($host, ContestJoinRequestedNotification::class);
        Notification::assertNotSentTo($user, ContestEntryStatusNotification::class);
    }

    public function test_auto_entry_notifies_the_user_and_leaves_the_host_alone(): void
    {
        Notification::fake();

        $host = User::factory()->create();
        $user = User::factory()->create();

        $contest = $this->makeContest([
            'host_id' => $host->id,
            'status' => 'open',
            'auto_entry' => true,
            'entry_deadline_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        Notification::assertSentTo($user, ContestEntryStatusNotification::class);
        Notification::assertNotSentTo($host, ContestJoinRequestedNotification::class);
    }

    // ────────────── helpers ────────────────────────────────────────────

    private function makeContest(array $attributes = []): Contest
    {
        return Contest::withoutHostGuard(fn() => Contest::forceCreate(array_merge([
            'uuid' => (string) Str::uuid(),
            'name' => 'Entry Window Test',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ], $attributes)));
    }
}