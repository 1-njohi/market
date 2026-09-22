<?php

namespace Tests\Feature\Notifications;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Services\BetslipSettlementService;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class WatcherSettlementNotificationTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.betslip_pirates.fee_tiers' => [
                ['max' => null, 'percentage' => 0.10],
            ],
        ]);
    }

    public function test_watcher_receives_notification_when_watched_betslip_wins(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        [$watcher] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'won', price: 200.00);
        $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        app(WatchlistService::class)->watch($watcher, $betslip);

        app(BetslipSettlementService::class)->settle($betslip);

        $data = $this->watcherNotificationData($watcher);

        $this->assertNotNull($data, 'Watcher should receive a notification.');
        $this->assertSame('watch_settled', $data['type']);
        $this->assertSame('won', $data['outcome']);
        $this->assertSame($betslip->code, $data['betslip_code']);
        $this->assertNotEmpty($data['title']);
        $this->assertNotEmpty($data['body']);
    }

    public function test_watcher_receives_notification_when_watched_betslip_loses(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        [$watcher] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'lost', price: 200.00);
        $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        app(WatchlistService::class)->watch($watcher, $betslip);

        app(BetslipSettlementService::class)->settle($betslip);

        $data = $this->watcherNotificationData($watcher);

        $this->assertNotNull($data);
        $this->assertSame('watch_settled', $data['type']);
        $this->assertSame('refunded', $data['outcome']);
    }

    public function test_watcher_receives_notification_when_watched_betslip_is_voided(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        [$watcher] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'void', price: 200.00);
        $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        app(WatchlistService::class)->watch($watcher, $betslip);

        app(BetslipSettlementService::class)->settle($betslip);

        $data = $this->watcherNotificationData($watcher);

        $this->assertNotNull($data);
        $this->assertSame('watch_settled', $data['type']);
        $this->assertSame('voided', $data['outcome']);
    }

    public function test_watcher_who_is_also_a_buyer_does_not_get_a_watcher_notification(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'won', price: 200.00);
        $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        // Buyer watches the same slip they bought — bypass the service guard
        // (which correctly rejects this) to test the dispatch-side exclusion.
        $buyer->watchedBetslips()->attach($betslip->id, [
            'watched_at' => now(),
        ]);

        app(BetslipSettlementService::class)->settle($betslip);

        // Buyer should receive exactly one notification: betslip_won, not
        // watch_settled as well.
        $rows = DB::table('notifications')
            ->where('notifiable_id', $buyer->id)
            ->where('notifiable_type', User::class)
            ->get();

        $this->assertCount(1, $rows);

        $data = json_decode($rows->first()->data, true);
        $this->assertSame('betslip_won', $data['type']);
    }

    // --------------------- helpers ---------------------

    private function watcherNotificationData(User $user): ?array
    {
        $row = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"watch_settled"%')
            ->orderByDesc('created_at')
            ->first();

        return $row ? json_decode($row->data, true) : null;
    }

    private function makeBetslipWithLeg(User $seller, string $legStatus, float $price): Betslip
    {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 1,
            'code' => 'BS-TEST-' . uniqid(),
            'is_winner' => false,
        ]);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        try {
            DB::table('betslip_odd')->insert([
                'betslip_id' => $betslip->id,
                'odd_id' => 900000,
                'status' => $legStatus,
                'odd_value_at_time' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        return $betslip->fresh();
    }

    private function makePendingPurchase(
        User $buyer,
        User $seller,
        Betslip $betslip,
        float $price,
    ): BetslipUserPurchase {
        return BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => $price,
            'total_odds' => 3.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }
}