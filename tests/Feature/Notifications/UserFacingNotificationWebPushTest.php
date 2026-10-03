<?php

namespace Tests\Feature\Notifications;

use App\Models\Betslip;
use App\Models\Contest;
use App\Models\Deposit;
use App\Models\User;
use App\Notifications\BetslipLostNotification;
use App\Notifications\BetslipVoidedNotification;
use App\Notifications\BetslipWonNotification;
use App\Notifications\ContestEntryStatusNotification;
use App\Notifications\ContestSettledNotification;
use App\Notifications\DepositConfirmedNotification;
use App\Notifications\ReferralAttributedNotification;
use App\Notifications\ReferralRewardNotification;
use App\Notifications\ReferralSignupNotification;
use App\Notifications\WatcherSettlementNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserFacingNotificationWebPushTest extends TestCase
{
    use DatabaseMigrations;

    #[DataProvider('notifications')]
    public function test_it_always_declares_the_database_channel(string $key, string $_url): void
    {
        [$notification, $notifiable] = $this->build($key);

        $this->assertContains('database', $notification->via($notifiable));
    }

    #[DataProvider('notifications')]
    public function test_it_omits_webpush_when_recipient_has_no_subscription(string $key, string $_url): void
    {
        [$notification, $notifiable] = $this->build($key);

        $this->assertNotContains(
            WebPushChannel::class,
            $notification->via($notifiable),
        );
    }

    #[DataProvider('notifications')]
    public function test_it_includes_webpush_when_recipient_is_subscribed(string $key, string $_url): void
    {
        [$notification, $notifiable] = $this->build($key);
        $notifiable->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test',
            'pk',
            'at',
        );

        $this->assertContains(
            WebPushChannel::class,
            $notification->via($notifiable),
        );
    }

    #[DataProvider('notifications')]
    public function test_it_produces_a_well_formed_payload(string $key, string $expectedUrlPrefix): void
    {
        [$notification, $notifiable] = $this->build($key);

        $message = $notification->toWebPush($notifiable, $notification);

        $this->assertInstanceOf(WebPushMessage::class, $message);

        $payload = $message->toArray();

        $this->assertNotEmpty($payload['title'] ?? null, "[{$key}] title is empty.");
        $this->assertNotEmpty($payload['body'] ?? null, "[{$key}] body is empty.");

        $url = $payload['data']['url'] ?? null;
        $this->assertNotNull($url, "[{$key}] payload has no data.url.");
        $this->assertStringStartsWith(
            $expectedUrlPrefix,
            $url,
            "[{$key}] url does not start with {$expectedUrlPrefix}.",
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Data provider
    // ─────────────────────────────────────────────────────────────

    public static function notifications(): array
    {
        return [
            'betslip_won'            => ['betslip_won',            '/betslip/view/g/'],
            'betslip_lost'           => ['betslip_lost',           '/betslip/view/g/'],
            'betslip_voided'         => ['betslip_voided',         '/betslip/view/g/'],
            'watcher_settlement'     => ['watcher_settlement',     '/betslip/view/g/'],
            'contest_entry_accepted' => ['contest_entry_accepted', '/contests/'],
            'contest_entry_rejected' => ['contest_entry_rejected', '/contests/mine'],
            'contest_settled'        => ['contest_settled',        '/contests/'],
            'referral_signup'        => ['referral_signup',        '/refer'],
            'referral_attributed'    => ['referral_attributed',    '/dashboard'],
            'referral_reward'        => ['referral_reward',        '/refer'],
            'deposit_confirmed'      => ['deposit_confirmed',      '/dashboard'],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Fixture builder
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{0: \Illuminate\Notifications\Notification, 1: User}
     */
    private function build(string $key): array
    {
        $seller    = User::factory()->create(['name' => 'Seller',  'code' => 'SELLR001']);
        $buyer     = User::factory()->create(['name' => 'Buyer',   'code' => 'BUYER001']);
        $host      = User::factory()->create(['name' => 'Host',    'code' => 'HOST0001']);
        $requester = User::factory()->create(['name' => 'Alice',   'code' => 'ALICE001']);

        $betslip = Betslip::create([
            'user_id'    => $seller->id,
            'code'       => 'ABC-DEFG-HIJ',
            'price'      => 250,
            'total_odds' => 2.50,
            'status'     => 'pending',
            'remaining'  => 3,
            'is_winner'  => false,
        ]);

        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Sunday Crew',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at'         => now()->addDay()->addHour(),
            'ends_at'           => now()->addDay()->addHours(2),
        ]);

        $deposit = Deposit::create([
            'user_id'   => $seller->id,
            'amount'    => 500,
            'reference' => 'DEP-001',
            'status'    => 'confirmed',
        ]);

        return match ($key) {
            'betslip_won' => [
                new BetslipWonNotification($betslip),
                $seller,
            ],
            'betslip_lost' => [
                new BetslipLostNotification($betslip),
                $seller,
            ],
            'betslip_voided' => [
                new BetslipVoidedNotification($betslip),
                $seller,
            ],
            'watcher_settlement' => [
                new WatcherSettlementNotification($betslip, 'won'),
                $buyer,
            ],
            'contest_entry_accepted' => [
                new ContestEntryStatusNotification($contest, $host, 'accepted'),
                $requester,
            ],
            'contest_entry_rejected' => [
                new ContestEntryStatusNotification($contest, $host, 'rejected'),
                $requester,
            ],
            'contest_settled' => [
                new ContestSettledNotification(
                    $contest,
                    rank: 1,
                    correct: 5,
                    units: 10.0,
                    totalParticipants: 20,
                ),
                $requester,
            ],
            'referral_signup' => [
                new ReferralSignupNotification($requester, 0.10),
                $seller,
            ],
            'referral_attributed' => [
                new ReferralAttributedNotification($seller, 0.05, 500.0),
                $requester,
            ],
            'referral_reward' => [
                new ReferralRewardNotification($requester, $betslip, 25.0),
                $seller,
            ],
            'deposit_confirmed' => [
                new DepositConfirmedNotification($deposit),
                $seller,
            ],
        };
    }
}