<?php

namespace Tests\Feature\Model;

use App\Exceptions\BetslipImmutableException;
use App\Models\Betslip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BetslipObserverTest extends TestCase
{
    use DatabaseMigrations;

    public function test_price_cannot_be_changed_after_creation(): void
    {
        $betslip = $this->makeBetslip(price: 100.00);

        $this->expectException(BetslipImmutableException::class);
        $betslip->update(['price' => 200.00]);
    }

    public function test_total_odds_cannot_be_changed_after_creation(): void
    {
        $betslip = $this->makeBetslip();

        $this->expectException(BetslipImmutableException::class);
        $betslip->update(['total_odds' => 99.99]);
    }

    public function test_code_cannot_be_changed_after_creation(): void
    {
        $betslip = $this->makeBetslip();

        $this->expectException(BetslipImmutableException::class);
        $betslip->update(['code' => 'NEW-CODE']);
    }

    public function test_user_id_cannot_be_changed_after_creation(): void
    {
        $betslip = $this->makeBetslip();
        $other = User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        $this->expectException(BetslipImmutableException::class);
        $betslip->update(['user_id' => $other->id]);
    }

    public function test_caption_cannot_be_changed_after_creation(): void
    {
        $betslip = $this->makeBetslip();

        $this->expectException(BetslipImmutableException::class);
        $betslip->update(['caption' => 'New caption']);
    }

    public function test_status_can_be_changed(): void
    {
        $betslip = $this->makeBetslip();

        $betslip->update(['status' => 'settled']);

        $this->assertSame('settled', $betslip->fresh()->status);
    }

    public function test_remaining_can_be_changed(): void
    {
        $betslip = $this->makeBetslip();

        $betslip->update(['remaining' => 2]);

        $this->assertSame(2, $betslip->fresh()->remaining);
    }

    public function test_is_winner_can_be_changed(): void
    {
        $betslip = $this->makeBetslip();

        $betslip->update(['is_winner' => true]);

        $this->assertTrue((bool) $betslip->fresh()->is_winner);
    }

    public function test_deleting_is_prevented(): void
    {
        $betslip = $this->makeBetslip();

        $this->expectException(BetslipImmutableException::class);
        $betslip->delete();
    }

    public function test_multiple_mutations_raise_on_first_immutable_field(): void
    {
        $betslip = $this->makeBetslip();

        $this->expectException(BetslipImmutableException::class);
        $betslip->update([
            'status' => 'settled',
            'price' => 500.00,
        ]);
    }

    private function makeBetslip(
        float $price = 100.00,
        string $caption = 'Test caption'
    ): Betslip {
        $seller = User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
            'caption' => $caption,
        ]);
    }
}