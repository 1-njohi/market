<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContestEntryNotificationsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_host_receives_notification_when_user_requests_to_join(): void
    {
        $host    = $this->makeUser('Host');
        $joiner  = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $this->actingAs($joiner)->post("/contests/join/{$contest->uuid}");

        $row = DB::table('notifications')
            ->where('notifiable_id', $host->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"contest_join_requested"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('contest_join_requested', $data['type']);
        $this->assertSame($contest->uuid, $data['contest_uuid']);
        $this->assertSame('Joiner', $data['requester_name']);
        $this->assertStringContainsString('Joiner', $data['body']);
    }

    public function test_joiner_receives_notification_when_accepted(): void
    {
        $host    = $this->makeUser('Host');
        $joiner  = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/accept");

        $row = DB::table('notifications')
            ->where('notifiable_id', $joiner->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"contest_entry_status"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('contest_entry_status', $data['type']);
        $this->assertSame('accepted', $data['status']);
        $this->assertSame($contest->uuid, $data['contest_uuid']);
        $this->assertSame('Host', $data['host_name']);
    }

    public function test_joiner_receives_notification_when_rejected(): void
    {
        $host    = $this->makeUser('Host');
        $joiner  = $this->makeUser('Joiner');
        $contest = $this->makeContest($host);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $this->actingAs($host)
            ->post("/contests/{$contest->id}/entries/{$entry->id}/reject");

        $row = DB::table('notifications')
            ->where('notifiable_id', $joiner->id)
            ->where('data', 'like', '%"type":"contest_entry_status"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('rejected', $data['status']);
    }

    public function test_no_notification_when_nothing_changed(): void
    {
        $this->makeUser('Host');
        $this->makeUser('Joiner');

        $this->assertSame(0, DB::table('notifications')
            ->where('data', 'like', '%"type":"contest_%')
            ->count());
    }

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
            'host_id'           => $host->id,
            'name'              => 'Sunday Crew',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(4),
            'ends_at'           => now()->addDays(5),
        ]);
    }
}