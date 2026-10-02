<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HostContestDashboardTest extends TestCase
{
    use DatabaseMigrations;

    public function test_host_sees_their_contests_listed(): void
    {
        $host = $this->makeUser('Denis');
        $other = $this->makeUser('NotHost');

        $c1 = $this->makeContest($host, name: 'Sunday Crew');
        $c2 = $this->makeContest($host, name: 'KPL Degens');
        $this->makeContest($other, name: 'Someone Else');

        $response = $this->actingAs($host)->get('/contests/mine');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Mine')
                ->has('contests', 2)
                ->where('contests.0.name', 'KPL Degens')  // newest first
                ->where('contests.1.name', 'Sunday Crew')
        );
    }

    public function test_each_contest_shows_pending_entry_count(): void
    {
        $host = $this->makeUser('Host');
        $contest = $this->makeContest($host);

        $this->makeEntry($contest, $this->makeUser('A'), 'pending');
        $this->makeEntry($contest, $this->makeUser('B'), 'pending');
        $this->makeEntry($contest, $this->makeUser('C'), 'accepted');

        $response = $this->actingAs($host)->get('/contests/mine');

        $response->assertInertia(
            fn ($page) => $page
                ->where('contests.0.pending_entries', 2)
                ->where('contests.0.accepted_entries', 1)
        );
    }

    public function test_guest_cannot_view_host_dashboard(): void
    {
        $response = $this->get('/contests/mine');

        $response->assertRedirect('/login');
    }

    public function test_host_can_view_pending_entries_for_a_contest(): void
    {
        $host = $this->makeUser('Host');
        $alice = $this->makeUser('Alice');
        $bob   = $this->makeUser('Bob');

        $contest = $this->makeContest($host);
        $this->makeEntry($contest, $alice, 'pending');
        $this->makeEntry($contest, $bob, 'accepted');

        $response = $this->actingAs($host)
            ->get("/contests/{$contest->id}/manage");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Manage')
                ->has('contest.entries', 2)
                ->where('contest.entries.0.user.name', 'Alice')
                ->where('contest.entries.0.status', 'pending')
        );
    }

    public function test_non_host_cannot_view_manage_page(): void
    {
        $host  = $this->makeUser('Host');
        $other = $this->makeUser('Other');
        $contest = $this->makeContest($host);

        $response = $this->actingAs($other)
            ->get("/contests/{$contest->id}/manage");

        $response->assertStatus(403);
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
    ): Contest {
        return Contest::create([
            'host_id'           => $host->id,
            'name'              => $name,
            'visibility'        => 'private',
            'status'            => $status,
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(4),
            'ends_at'           => now()->addDays(5),
        ]);
    }

    private function makeEntry(Contest $contest, User $user, string $status): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => $status,
            'joined_at'  => now(),
        ]);
    }
}