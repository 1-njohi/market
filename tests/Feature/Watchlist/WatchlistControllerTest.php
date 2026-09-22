<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use App\Services\WatchlistService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WatchlistControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_watch_redirects_back_with_success_flash(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $response = $this->actingAs($buyer)
            ->from('/marketplace')
            ->post("/betslip/{$betslip->code}/watch");

        $response->assertRedirect('/marketplace');
        $response->assertSessionHas('success');

        $this->assertStringContainsStringIgnoringCase(
            'watch',
            session('success')
        );

        $this->assertDatabaseHas('betslip_watches', [
            'user_id' => $buyer->id,
            'betslip_id' => $betslip->id,
        ]);
    }

    public function test_unwatch_redirects_back_with_success_flash(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        app(WatchlistService::class)->watch($buyer, $betslip);

        $response = $this->actingAs($buyer)
            ->from('/watchlist')
            ->delete("/betslip/{$betslip->code}/watch");

        $response->assertRedirect('/watchlist');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('betslip_watches', [
            'user_id' => $buyer->id,
            'betslip_id' => $betslip->id,
        ]);
    }

    public function test_watching_own_betslip_redirects_with_error_flash(): void
    {
        $seller = $this->makeUser();
        $betslip = $this->makeBetslip(seller: $seller);

        $response = $this->actingAs($seller)
            ->from('/marketplace')
            ->post("/betslip/{$betslip->code}/watch");

        $response->assertRedirect('/marketplace');
        $response->assertSessionHas('error');

        $this->assertStringContainsStringIgnoringCase(
            'own',
            session('error')
        );
    }

    public function test_watching_settled_betslip_redirects_with_error_flash(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip(status: 'settled');

        $response = $this->actingAs($buyer)
            ->from('/marketplace')
            ->post("/betslip/{$betslip->code}/watch");

        $response->assertRedirect('/marketplace');
        $response->assertSessionHas('error');
    }

    public function test_watching_unknown_code_redirects_with_error_flash(): void
    {
        $buyer = $this->makeUser();

        $response = $this->actingAs($buyer)
            ->from('/marketplace')
            ->post('/betslip/DOES-NOT-EXIST/watch');

        $response->assertRedirect('/marketplace');
        $response->assertSessionHas('error');
    }

    public function test_watch_is_idempotent_over_http(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $this->actingAs($buyer)->from('/marketplace')
            ->post("/betslip/{$betslip->code}/watch")->assertRedirect();

        $this->actingAs($buyer)->from('/marketplace')
            ->post("/betslip/{$betslip->code}/watch")->assertRedirect();

        $this->assertSame(1, $buyer->watchedBetslips()->count());
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeBetslip(
        ?User $seller = null,
        string $status = 'pending'
    ): Betslip {
        $seller ??= $this->makeUser();

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