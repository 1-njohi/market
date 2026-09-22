<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WatchRecordPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_record_page_renders_with_empty_record(): void
    {
        $buyer = $this->makeUser();

        $response = $this->actingAs($buyer)->get('/watchlist/record');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('WatchlistRecord')
                ->has('record')
                ->where('record.settled_count', 0)
                ->where('record.units', 0)
                ->where('record.has_enough_data', false)
                ->has('record.sellers', 0)
        );
    }

    public function test_record_page_reflects_settled_watches(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeUser();
        $service = app(WatchlistService::class);

        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-W1');
        $this->watchSettled($buyer, $seller, 'settled', true, 3.00, 'BS-W2');
        $this->watchSettled($buyer, $seller, 'settled', false, 1.50, 'BS-L1');

        $response = $this->actingAs($buyer)->get('/watchlist/record');

        $response->assertInertia(
            fn($page) => $page
                ->component('WatchlistRecord')
                ->where('record.settled_count', 3)
                ->where('record.won_count', 2)
                ->where('record.lost_count', 1)
                ->where('record.units', 2)
                ->where('record.has_enough_data', true)
                ->has('record.sellers', 1)
                ->where('record.sellers.0.seller_id', $seller->id)
        );
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/watchlist/record');

        $response->assertRedirect('/login');
    }

    private function watchSettled(
        User $buyer,
        User $seller,
        string $status,
        bool $isWinner,
        float $totalOdds,
        string $code,
    ): void {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => $totalOdds,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 0,
            'code' => $code,
            'is_winner' => $isWinner,
        ]);

        $buyer->watchedBetslips()->attach($betslip->id, [
            'watched_at' => now(),
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}