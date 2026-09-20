<?php

namespace Tests\Feature\Notifications;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Services\BetslipPurchaseService;
use App\Services\BetslipSettlementService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class SettlementNotificationTest extends TestCase
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

    public function test_buyer_and_seller_receive_notifications_when_betslip_wins(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithWonLeg($seller, price: 200.00);

        app(BetslipPurchaseService::class)->purchase($buyer, $betslip);
        app(BetslipSettlementService::class)->settle($betslip);

        // Buyer: one notification of type betslip_won.
        $buyerNotification = DB::table('notifications')
            ->where('notifiable_id', $buyer->id)
            ->where('notifiable_type', User::class)
            ->first();

        $this->assertNotNull($buyerNotification, 'Buyer should have received a notification.');

        $buyerData = json_decode($buyerNotification->data, true);
        $this->assertSame('betslip_won', $buyerData['type']);
        $this->assertSame($betslip->code, $buyerData['betslip_code']);
        $this->assertNotEmpty($buyerData['title']);
        $this->assertNotEmpty($buyerData['body']);

        // Seller: one notification of type betslip_won.
        $sellerNotification = DB::table('notifications')
            ->where('notifiable_id', $seller->id)
            ->where('notifiable_type', User::class)
            ->first();

        $this->assertNotNull($sellerNotification, 'Seller should have received a notification.');

        $sellerData = json_decode($sellerNotification->data, true);
        $this->assertSame('betslip_won', $sellerData['type']);
        $this->assertSame($betslip->code, $sellerData['betslip_code']);

        // The messages should differ — buyer and seller shouldn't get
        // the same wording.
        $this->assertNotSame($buyerData['body'], $sellerData['body']);
    }

    private function makeBetslipWithWonLeg(User $seller, float $price): Betslip
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
                'status' => 'won',
                'odd_value_at_time' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        return $betslip->fresh();
    }
    public function test_buyer_and_seller_receive_notifications_when_betslip_loses(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'lost', price: 200.00);
        $purchase = $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        app(\App\Services\BetslipSettlementService::class)->settle($betslip);

        $buyerData = $this->notificationDataFor($buyer);
        $sellerData = $this->notificationDataFor($seller);

        $this->assertNotNull($buyerData);
        $this->assertSame('betslip_lost', $buyerData['type']);
        $this->assertStringContainsString('refund', strtolower($buyerData['body']));

        $this->assertNotNull($sellerData);
        $this->assertSame('betslip_lost', $sellerData['type']);
        $this->assertStringNotContainsString('refund', strtolower($sellerData['body']));
        $this->assertNotSame($buyerData['body'], $sellerData['body']);
    }

    public function test_buyer_and_seller_receive_notifications_when_betslip_voids(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLeg($seller, 'void', price: 200.00);
        $purchase = $this->makePendingPurchase($buyer, $seller, $betslip, 200.00);

        app(\App\Services\BetslipSettlementService::class)->settle($betslip);

        $buyerData = $this->notificationDataFor($buyer);
        $sellerData = $this->notificationDataFor($seller);

        $this->assertNotNull($buyerData);
        $this->assertSame('betslip_voided', $buyerData['type']);
        $this->assertStringContainsString('void', strtolower($buyerData['body']));

        $this->assertNotNull($sellerData);
        $this->assertSame('betslip_voided', $sellerData['type']);
        $this->assertStringContainsString('void', strtolower($sellerData['body']));
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

    private function notificationDataFor(User $user): ?array
    {
        $row = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->orderByDesc('created_at')
            ->first();

        return $row ? json_decode($row->data, true) : null;
    }
}