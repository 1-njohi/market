<?php

namespace Tests\Feature\Notifications;

use App\Models\Contest;
use App\Models\User;
use App\Notifications\ContestJoinRequestedNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Tests\TestCase;

class ContestJoinRequestedWebPushTest extends TestCase
{
    use DatabaseMigrations;


    public function test_it_still_declares_the_database_channel(): void
    {
        [$notification, $host] = $this->fixture();

        $this->assertContains('database', $notification->via($host));
    }

    public function test_it_produces_a_well_formed_webpush_payload(): void
    {
        [$notification, $host, $contest] = $this->fixture();

        $message = $notification->toWebPush($host, $notification);

        $this->assertInstanceOf(WebPushMessage::class, $message);

        $payload = $message->toArray();

        $this->assertNotEmpty($payload['title'] ?? null, 'Title must not be empty.');
        $this->assertNotEmpty($payload['body'] ?? null, 'Body must not be empty.');

        $this->assertSame(
            "/contests/{$contest->id}/manage",
            $payload['data']['url'] ?? null,
            'Webpush payload must deep-link to the host manage page.',
        );
    }

    public function test_it_omits_the_webpush_channel_when_recipient_has_no_subscription(): void
    {
        [$notification, $host] = $this->fixture();
        // $host has no push subscription in the fixture.

        $this->assertNotContains(
            WebPushChannel::class,
            $notification->via($host),
        );
    }

    public function test_it_declares_the_webpush_channel_when_recipient_has_a_subscription(): void
    {
        [$notification, $host] = $this->fixture();

        $host->updatePushSubscription('https://fcm.googleapis.com/fcm/send/test', 'pk', 'at');

        $this->assertContains(
            WebPushChannel::class,
            $notification->via($host),
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{0: ContestJoinRequestedNotification, 1: User, 2: Contest}
     */
    private function fixture(): array
    {
        $host = User::factory()->create([
            'name' => 'Host Person',
            'code' => 'HOST0001',
        ]);

        $requester = User::factory()->create([
            'name' => 'Alice',
            'code' => 'ALICE001',
        ]);

        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Sunday Crew',
            'description'       => 'Weekly picks.',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at'         => now()->addDay()->addHour(),
            'ends_at'           => now()->addDay()->addHours(2),
        ]);

        return [
            new ContestJoinRequestedNotification($contest, $requester),
            $host,
            $contest,
        ];
    }
}