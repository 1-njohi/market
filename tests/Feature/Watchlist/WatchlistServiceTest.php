<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WatchlistServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_watch_is_idempotent(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();
        $service = app(WatchlistService::class);

        $this->assertTrue($service->watch($buyer, $betslip));
        $this->assertTrue($service->watch($buyer, $betslip));
        $this->assertSame(1, $buyer->watchedBetslips()->count());
    }

    public function test_unwatch_is_idempotent(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();
        $service = app(WatchlistService::class);

        $service->watch($buyer, $betslip);

        $this->assertTrue($service->unwatch($buyer, $betslip));
        $this->assertTrue($service->unwatch($buyer, $betslip));
        $this->assertSame(0, $buyer->watchedBetslips()->count());
    }

    public function test_is_watching_reflects_state(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();
        $service = app(WatchlistService::class);

        $this->assertFalse($service->isWatching($buyer, $betslip));

        $service->watch($buyer, $betslip);
        $this->assertTrue($service->isWatching($buyer, $betslip));

        $service->unwatch($buyer, $betslip);
        $this->assertFalse($service->isWatching($buyer, $betslip));
    }

    public function test_watch_records_watched_at_timestamp(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();
        $service = app(WatchlistService::class);

        $service->watch($buyer, $betslip);

        $pivot = $buyer->watchedBetslips()->first()->pivot;
        $this->assertNotNull($pivot->watched_at);
    }
    public function test_user_cannot_watch_their_own_betslip(): void
    {
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);

        $this->expectException(\App\Exceptions\WatchlistException::class);

        $service->watch($seller, $betslip);
    }

    public function test_cannot_watch_a_settled_betslip(): void
    {
        $buyer = $this->makeUser();
        $service = app(WatchlistService::class);
        $betslip = $this->makeBetslip(status: 'settled');

        $this->expectException(\App\Exceptions\WatchlistException::class);

        $service->watch($buyer, $betslip);
    }

    public function test_cannot_watch_a_voided_betslip(): void
    {
        $buyer = $this->makeUser();
        $service = app(WatchlistService::class);
        $betslip = $this->makeBetslip(status: 'voided');

        $this->expectException(\App\Exceptions\WatchlistException::class);

        $service->watch($buyer, $betslip);
    }

    public function test_cannot_watch_a_betslip_already_purchased(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);

        \App\Models\BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 100.00,
            'total_odds' => 3.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);

        $this->expectException(\App\Exceptions\WatchlistException::class);

        $service->watch($buyer, $betslip);
    }

    public function test_watch_cap_is_enforced(): void
    {
        $buyer = $this->makeUser();
        $service = app(WatchlistService::class);

        for ($i = 0; $i < WatchlistService::MAX_ACTIVE_WATCHES; $i++) {
            $service->watch($buyer, $this->makeBetslip());
        }

        $extra = $this->makeBetslip();

        $this->expectException(\App\Exceptions\WatchlistException::class);

        $service->watch($buyer, $extra);
    }

    public function test_settled_watches_dont_count_toward_cap(): void
    {
        $buyer = $this->makeUser();
        $service = app(WatchlistService::class);

        // Seed the cap with settled bets — bypass the guard via raw attach.
        for ($i = 0; $i < WatchlistService::MAX_ACTIVE_WATCHES; $i++) {
            $settled = $this->makeBetslip(status: 'settled');
            $buyer->watchedBetslips()->attach($settled->id, [
                'watched_at' => now(),
            ]);
        }

        // A fresh pending slip should still be watchable.
        $fresh = $this->makeBetslip();

        $this->assertTrue($service->watch($buyer, $fresh));
    }
    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeBetslip(string $status = 'pending'): Betslip
    {
        $seller = $this->makeUser();

        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 3,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);
    }
}