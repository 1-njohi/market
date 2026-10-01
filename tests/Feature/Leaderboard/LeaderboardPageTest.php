<?php

namespace Tests\Feature\Leaderboard;

use App\Models\Betslip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LeaderboardPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_page_renders_with_default_window(): void
    {
        $seller = $this->makeUser('Denis');
        $this->settleWon($seller, odds: 3.00);

        $response = $this->get('/leaderboard');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Leaderboard')
                ->where('window', '90d')
                ->has('leaders.data', 1)
                ->where('leaders.data.0.name', 'Denis')
                ->where('leaders.data.0.rank', 1)
                ->where('leaders.data.0.units', 2)
        );
    }

    public function test_window_query_param_is_honoured(): void
    {
        $seller = $this->makeUser('Old');
        $this->settleWon($seller, odds: 3.00, settledAt: now()->subDays(45));

        // 30d window — the 45-day-old slip shouldn't appear.
        $thirty = $this->get('/leaderboard?window=30d');
        $thirty->assertInertia(fn($page) => $page->has('leaders.data', 0));

        // 90d window — it should.
        $ninety = $this->get('/leaderboard?window=90d');
        $ninety->assertInertia(fn($page) => $page->has('leaders.data', 1));
    }

    public function test_invalid_window_falls_back_to_default(): void
    {
        $response = $this->get('/leaderboard?window=garbage');

        $response->assertOk();
        $response->assertInertia(fn($page) => $page->where('window', '90d'));
    }

    public function test_page_is_publicly_accessible(): void
    {
        $response = $this->get('/leaderboard');

        $response->assertOk();
    }

    public function test_pagination_links_reflect_window(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            $seller = $this->makeUser("Seller {$i}");
            $this->settleWon($seller, odds: 1.00 + ($i / 10));
        }

        $response = $this->get('/leaderboard?window=30d&page=2');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->where('window', '30d')
                ->has('leaders.data', 5)
                ->where('leaders.data.0.rank', 51)
        );
    }

    // ------------------ helpers ------------------

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function settleWon(User $seller, float $odds, ?\Carbon\CarbonInterface $settledAt = null): void
    {
        $at = $settledAt ?? now();

        $slip = new Betslip([
            'user_id' => $seller->id,
            'total_odds' => $odds,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => true,
        ]);
        $slip->timestamps = false;
        $slip->created_at = $at;
        $slip->updated_at = $at;
        $slip->save();
    }
}