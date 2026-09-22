<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WatchRecordTest extends TestCase
{
    use DatabaseMigrations;

    public function test_record_is_empty_for_user_with_no_settled_watches(): void
    {
        $buyer = $this->makeUser();

        $record = app(WatchlistService::class)->getWatchRecord($buyer);

        $this->assertSame(0, $record['settled_count']);
        $this->assertSame(0, $record['won_count']);
        $this->assertSame(0, $record['lost_count']);
        $this->assertSame(0, $record['voided_count']);
        $this->assertSame(0.0, $record['units']);
        $this->assertFalse($record['has_enough_data']);
        $this->assertSame([], $record['sellers']);
    }

    public function test_record_counts_outcomes(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-W1');
        $this->watchSettled($buyer, $seller, 'settled', true, 3.00, 'BS-W2');
        $this->watchSettled($buyer, $seller, 'settled', false, 1.50, 'BS-L1');
        $this->watchSettled($buyer, $seller, 'voided', false, 4.00, 'BS-V1');

        $record = $service->getWatchRecord($buyer);

        $this->assertSame(4, $record['settled_count']);
        $this->assertSame(2, $record['won_count']);
        $this->assertSame(1, $record['lost_count']);
        $this->assertSame(1, $record['voided_count']);
        $this->assertTrue($record['has_enough_data']);
    }

    public function test_record_computes_units_at_flat_stake(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        // (2.00 - 1) + (3.00 - 1) - 1 - 0 = 1.00 + 2.00 - 1.00 = 2.00
        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-W1');
        $this->watchSettled($buyer, $seller, 'settled', true, 3.00, 'BS-W2');
        $this->watchSettled($buyer, $seller, 'settled', false, 1.50, 'BS-L1');
        $this->watchSettled($buyer, $seller, 'voided', false, 4.00, 'BS-V1');

        $record = $service->getWatchRecord($buyer);

        $this->assertSame(2.00, $record['units']);
    }

    public function test_record_flags_insufficient_data_under_threshold(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-W1');
        $this->watchSettled($buyer, $seller, 'settled', false, 1.50, 'BS-L1');

        $record = $service->getWatchRecord($buyer);

        $this->assertSame(2, $record['settled_count']);
        $this->assertFalse($record['has_enough_data']);
    }

    public function test_record_groups_by_seller_with_min_two_settled(): void
    {
        $buyer = $this->makeUser();
        $sellerA = $this->makeUser();
        $sellerB = $this->makeUser();
        $sellerC = $this->makeUser();
        $service = app(WatchlistService::class);

        // Seller A: 2 won → +2.00u
        $this->watchSettled($buyer, $sellerA, 'settled', true, 2.00, 'BS-A1');
        $this->watchSettled($buyer, $sellerA, 'settled', true, 2.00, 'BS-A2');

        // Seller B: 1 won, 1 lost → 0.00u
        $this->watchSettled($buyer, $sellerB, 'settled', true, 2.00, 'BS-B1');
        $this->watchSettled($buyer, $sellerB, 'settled', false, 2.00, 'BS-B2');

        // Seller C: only 1 settled → excluded from breakdown
        $this->watchSettled($buyer, $sellerC, 'settled', true, 3.00, 'BS-C1');

        $record = $service->getWatchRecord($buyer);

        $this->assertCount(2, $record['sellers']);

        // Sorted by units descending — A first, then B.
        $this->assertSame($sellerA->id, $record['sellers'][0]['seller_id']);
        $this->assertSame(2.00, $record['sellers'][0]['units']);
        $this->assertSame(2, $record['sellers'][0]['settled_count']);
        $this->assertSame(2, $record['sellers'][0]['won_count']);

        $this->assertSame($sellerB->id, $record['sellers'][1]['seller_id']);
        $this->assertSame(0.00, $record['sellers'][1]['units']);
        $this->assertSame(2, $record['sellers'][1]['settled_count']);
        $this->assertSame(1, $record['sellers'][1]['won_count']);
    }

    public function test_record_ignores_active_watches(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        // Active watch, should not count.
        $active = $this->makeBetslip($seller, 'pending', false, 5.00, 'BS-ACT');
        $service->watch($buyer, $active);

        // Settled watch, should count.
        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-SET');

        $record = $service->getWatchRecord($buyer);

        $this->assertSame(1, $record['settled_count']);
        $this->assertSame(1, $record['won_count']);
    }

    // --------------------- helpers ---------------------

    private function watchSettled(
        User $buyer,
        User $seller,
        string $status,
        bool $isWinner,
        float $totalOdds,
        string $code,
    ): void {
        $betslip = $this->makeBetslip($seller, $status, $isWinner, $totalOdds, $code);

        $buyer->watchedBetslips()->attach($betslip->id, [
            'watched_at' => now(),
        ]);
    }

    private function makeBetslip(
        User $seller,
        string $status,
        bool $isWinner,
        float $totalOdds,
        string $code,
    ): Betslip {
        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => $totalOdds,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 0,
            'code' => $code,
            'is_winner' => $isWinner,
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
    
}