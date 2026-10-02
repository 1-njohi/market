<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContestJoinTest extends TestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_invite_page(): void
    {
        $host    = $this->makeUser('Denis');
        $contest = $this->makeContest($host, name: 'Sunday Crew');

        $response = $this->get("/contests/join/{$contest->uuid}");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Join')
                ->where('contest.uuid', $contest->uuid)
                ->where('contest.name', 'Sunday Crew')
                ->where('contest.host_name', 'Denis')
                ->where('entry', null)
                ->where('can_join', false)
        );
    }

    public function test_authenticated_user_sees_join_button(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $response = $this->actingAs($user)
            ->get("/contests/join/{$contest->uuid}");

        $response->assertInertia(
            fn ($page) => $page->where('can_join', true)
        );
    }

    public function test_authenticated_user_can_join(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $response = $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $response->assertRedirect("/contests/join/{$contest->uuid}");

        $this->assertDatabaseHas('contest_entries', [
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
        ]);
    }

    public function test_joining_twice_does_not_create_duplicate(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $this->actingAs($user)->post("/contests/join/{$contest->uuid}");
        $this->actingAs($user)->post("/contests/join/{$contest->uuid}");

        $this->assertSame(
            1,
            ContestEntry::where('contest_id', $contest->id)
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_host_cannot_join_their_own_contest(): void
    {
        $host    = $this->makeUser('Host');
        $contest = $this->makeContest($host);

        $response = $this->actingAs($host)
            ->post("/contests/join/{$contest->uuid}");

        $response->assertSessionHasErrors('join');
        $this->assertSame(0, ContestEntry::count());
    }

    public function test_cannot_join_after_deadline(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host, entryDeadline: now()->subDay());

        $response = $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $response->assertSessionHasErrors('join');
        $this->assertSame(0, ContestEntry::count());
    }

    public function test_cannot_join_a_settled_contest(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host, status: 'settled');

        $response = $this->actingAs($user)
            ->post("/contests/join/{$contest->uuid}");

        $response->assertSessionHasErrors('join');
    }

    public function test_unknown_uuid_returns_404(): void
    {
        $response = $this->get('/contests/join/CNT-DOESNT');

        $response->assertStatus(404);
    }

    public function test_host_can_accept_a_pending_entry(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $response = $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $response->assertRedirect();
        $this->assertSame('accepted', $entry->fresh()->status);
    }

    public function test_host_can_reject_a_pending_entry(): void
    {
        $host    = $this->makeUser('Host');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $response = $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/reject");

        $response->assertRedirect();
        $this->assertSame('rejected', $entry->fresh()->status);
    }

    public function test_non_host_cannot_accept_entries(): void
    {
        $host    = $this->makeUser('Host');
        $other   = $this->makeUser('NotHost');
        $user    = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $response = $this->actingAs($other)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $response->assertStatus(403);
        $this->assertSame('pending', $entry->fresh()->status);
    }

    // ------------------ helpers ------------------

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeContest(
        User $host,
        string $name = 'Test Contest',
        string $status = 'open',
        ?\Carbon\CarbonInterface $entryDeadline = null,
    ): Contest {
        $deadline = $entryDeadline ?? now()->addDays(3);

        return Contest::create([
            'host_id'           => $host->id,
            'name'              => $name,
            'visibility'        => 'private',
            'status'            => $status,
            'entry_deadline_at' => $deadline,
            'starts_at'         => $deadline->copy()->addDay(),
            'ends_at'           => $deadline->copy()->addDays(2),
        ]);
    }
}