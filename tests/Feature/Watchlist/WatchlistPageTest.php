<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WatchlistPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_watchlist_page_lists_only_active_watches(): void
    {
        $buyer = $this->makeUser();
        $service = app(WatchlistService::class);

        $active = $this->makeBetslip(status: 'pending');
        $settled = $this->makeBetslip(status: 'settled');

        $service->watch($buyer, $active);

        // Attach the settled one directly — bypass the guard, since we
        // need a row that shouldn't show up in the active list.
        $buyer->watchedBetslips()->attach($settled->id, [
            'watched_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($buyer)->get('/watchlist');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Watchlist')
                ->has('bet_slips.data', 1)
                ->where('bet_slips.data.0.code', $active->code)
        );
    }

    public function test_watchlist_page_is_empty_for_a_user_with_no_watches(): void
    {
        $buyer = $this->makeUser();

        $response = $this->actingAs($buyer)->get('/watchlist');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Watchlist')
                ->has('bet_slips.data', 0)
        );
    }

    public function test_watchlist_page_only_shows_the_current_users_watches(): void
    {
        $buyer = $this->makeUser();
        $other = $this->makeUser();
        $service = app(WatchlistService::class);

        $mine = $this->makeBetslip(status: 'pending');
        $theirs = $this->makeBetslip(status: 'pending');

        $service->watch($buyer, $mine);
        $service->watch($other, $theirs);

        $response = $this->actingAs($buyer)->get('/watchlist');

        $response->assertInertia(
            fn($page) => $page
                ->has('bet_slips.data', 1)
                ->where('bet_slips.data.0.code', $mine->code)
        );
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/watchlist');

        $response->assertRedirect('/login');
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