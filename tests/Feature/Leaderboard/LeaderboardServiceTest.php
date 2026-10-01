<?php

namespace Tests\Feature\Leaderboard;

use App\Models\Betslip;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_seller_with_no_settled_slips_does_not_appear(): void
    {
        $this->makeUser('Silent Seller');

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');

        $this->assertSame(0, $paginator->total());
    }

    public function test_single_win_seller_appears_immediately(): void
    {
        $seller = $this->makeUser('New Winner');
        $this->settleWon($seller, odds: 2.00);

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');

        $this->assertSame(1, $paginator->total());
        $this->assertSame($seller->id, $paginator->items()[0]['user_id']);
        $this->assertSame(1.00, $paginator->items()[0]['units']);
    }

    public function test_units_math_matches_watch_record_convention(): void
    {
        $seller = $this->makeUser('Math Check');
        $this->settleWon($seller, odds: 2.00);      // +1.00
        $this->settleWon($seller, odds: 3.00);      // +2.00
        $this->settleLost($seller, odds: 2.50);     // -1.00
        $this->settleVoid($seller);                 // +0.00

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');
        $row = $paginator->items()[0];

        $this->assertSame(2.00, $row['units']);
        $this->assertSame(4, $row['settled_count']);
        $this->assertSame(2, $row['won_count']);
    }

    public function test_sellers_sorted_by_units_descending(): void
    {
        $a = $this->makeUser('Seller A');
        $b = $this->makeUser('Seller B');
        $c = $this->makeUser('Seller C');

        $this->settleWon($a, odds: 2.00);   // +1.00
        $this->settleWon($b, odds: 5.00);   // +4.00
        $this->settleWon($c, odds: 3.00);   // +2.00

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');
        $ids = array_column($paginator->items(), 'user_id');

        $this->assertSame([$b->id, $c->id, $a->id], $ids);
    }

    public function test_settled_count_tiebreak(): void
    {
        $a = $this->makeUser('Seller A');
        $b = $this->makeUser('Seller B');

        // A: 2 slips, +2.00 net. B: 1 slip, +2.00 net.
        $this->settleWon($a, odds: 3.00);   // +2.00
        $this->settleLost($a, odds: 2.00);  // -1.00
        $this->settleWon($a, odds: 2.00);   // +1.00 — total +2.00, 3 slips

        $this->settleWon($b, odds: 3.00);   // +2.00, 1 slip

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');

        // A has more settled slips — ranks first.
        $this->assertSame($a->id, $paginator->items()[0]['user_id']);
        $this->assertSame($b->id, $paginator->items()[1]['user_id']);
    }

    public function test_name_alphabetical_final_tiebreak(): void
    {
        $zoe = $this->makeUser('Zoe');
        $alice = $this->makeUser('Alice');

        // Same units, same settled count.
        $this->settleWon($zoe, odds: 2.00);
        $this->settleWon($alice, odds: 2.00);

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');

        $this->assertSame($alice->id, $paginator->items()[0]['user_id']);
        $this->assertSame($zoe->id, $paginator->items()[1]['user_id']);
    }

    public function test_window_filter_excludes_old_slips(): void
    {
        $seller = $this->makeUser('Old Timer');
        $this->settleWon($seller, odds: 5.00, settledAt: now()->subDays(120));

        $ninetyDay = app(LeaderboardService::class)->ranked(window: '90d');
        $this->assertSame(0, $ninetyDay->total());

        $allTime = app(LeaderboardService::class)->ranked(window: 'all');
        $this->assertSame(1, $allTime->total());
    }

    public function test_thirty_day_window(): void
    {
        $seller = $this->makeUser('Recent');
        $this->settleWon($seller, odds: 3.00, settledAt: now()->subDays(45));

        $thirty = app(LeaderboardService::class)->ranked(window: '30d');
        $this->assertSame(0, $thirty->total());

        $ninety = app(LeaderboardService::class)->ranked(window: '90d');
        $this->assertSame(1, $ninety->total());
    }

    public function test_pagination_splits_correctly_and_ranks_are_global(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            $seller = $this->makeUser("Seller {$i}");
            // Higher i = more units.
            $this->settleWon($seller, odds: 1.00 + ($i / 10));
        }

        $service = app(LeaderboardService::class);

        $page1 = $service->ranked(window: '90d', page: 1);
        $page2 = $service->ranked(window: '90d', page: 2);

        $this->assertSame(55, $page1->total());
        $this->assertSame(50, $page1->count());
        $this->assertSame(5, $page2->count());

        // Page 1 rank starts at 1, page 2 rank starts at 51.
        $this->assertSame(1, $page1->items()[0]['rank']);
        $this->assertSame(50, $page1->items()[49]['rank']);
        $this->assertSame(51, $page2->items()[0]['rank']);
        $this->assertSame(55, $page2->items()[4]['rank']);
    }

    public function test_voided_slips_contribute_zero_units_but_count_as_settled(): void
    {
        $seller = $this->makeUser('Voided');
        $this->settleWon($seller, odds: 2.00);   // +1.00
        $this->settleVoid($seller);              // +0.00

        $paginator = app(LeaderboardService::class)->ranked(window: '90d');
        $row = $paginator->items()[0];

        $this->assertSame(1.00, $row['units']);
        $this->assertSame(2, $row['settled_count']);
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
        $this->makeSettledSlip($seller, $odds, isWinner: true, status: 'settled', settledAt: $settledAt);
    }

    private function settleLost(User $seller, float $odds, ?\Carbon\CarbonInterface $settledAt = null): void
    {
        $this->makeSettledSlip($seller, $odds, isWinner: false, status: 'settled', settledAt: $settledAt);
    }

    private function settleVoid(User $seller, ?\Carbon\CarbonInterface $settledAt = null): void
    {
        $this->makeSettledSlip($seller, 2.00, isWinner: false, status: 'voided', settledAt: $settledAt);
    }

    private function makeSettledSlip(
        User $seller,
        float $odds,
        bool $isWinner,
        string $status,
        ?\Carbon\CarbonInterface $settledAt = null,
    ): Betslip {
        $at = $settledAt ?? now();

        $slip = new Betslip([
            'user_id' => $seller->id,
            'total_odds' => $odds,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 0,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => $isWinner,
        ]);
        $slip->timestamps = false;
        $slip->created_at = $at;
        $slip->updated_at = $at;
        $slip->save();

        return $slip;
    }
}