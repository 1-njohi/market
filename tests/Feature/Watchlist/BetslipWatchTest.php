<?php

namespace Tests\Feature\Watchlist;

use App\Models\Betslip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BetslipWatchTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_can_watch_a_betslip(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $buyer->watchedBetslips()->attach($betslip->id, [
            'watched_at' => now(),
        ]);

        $this->assertSame(1, $buyer->watchedBetslips()->count());
        $this->assertTrue($buyer->fresh()->watchedBetslips->contains($betslip->id));
    }

    public function test_user_cannot_watch_the_same_betslip_twice(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $buyer->watchedBetslips()->attach($betslip->id, ['watched_at' => now()]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $buyer->watchedBetslips()->attach($betslip->id, ['watched_at' => now()]);
    }

    public function test_user_can_unwatch_a_betslip(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $buyer->watchedBetslips()->attach($betslip->id, ['watched_at' => now()]);
        $this->assertSame(1, $buyer->watchedBetslips()->count());

        $buyer->watchedBetslips()->detach($betslip->id);
        $this->assertSame(0, $buyer->watchedBetslips()->count());
    }

    public function test_betslip_knows_its_watchers(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $buyer->watchedBetslips()->attach($betslip->id, ['watched_at' => now()]);

        $this->assertSame(1, $betslip->watchers()->count());
        $this->assertTrue($betslip->fresh()->watchers->contains($buyer->id));
    }

    public function test_watch_stores_watched_at_timestamp(): void
    {
        $buyer = $this->makeUser();
        $betslip = $this->makeBetslip();

        $buyer->watchedBetslips()->attach($betslip->id, ['watched_at' => now()]);

        $row = DB::table('betslip_watches')
            ->where('user_id', $buyer->id)
            ->where('betslip_id', $betslip->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertNotNull($row->watched_at);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeBetslip(): Betslip
    {
        $seller = $this->makeUser();

        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);
    }
}