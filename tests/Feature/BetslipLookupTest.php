<?php

namespace Tests\Feature;

use App\Models\Betslip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class BetslipLookupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_lookup_returns_url_for_an_existing_code(): void
    {
        $code = 'ABC-DEFG-HIJ';
        $this->makeBetslip($code);

        $response = $this->getJson('/betslips/lookup?code=' . $code);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'code'    => $code,
        ]);
        $response->assertJsonPath('url', "/betslip/view/g/{$code}");
    }

    public function test_lookup_is_case_insensitive(): void
    {
        $this->makeBetslip('ABC-DEFG-HIJ');

        $response = $this->getJson('/betslips/lookup?code=abc-defg-hij');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'code'    => 'ABC-DEFG-HIJ',
        ]);
    }

    public function test_lookup_trims_whitespace(): void
    {
        $this->makeBetslip('ABC-DEFG-HIJ');

        $response = $this->getJson('/betslips/lookup?code=' . urlencode('  ABC-DEFG-HIJ  '));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_lookup_returns_404_for_unknown_code(): void
    {
        $response = $this->getJson('/betslips/lookup?code=NOPE-NOPE-NOP');

        $response->assertNotFound();
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_lookup_returns_422_when_code_is_empty(): void
    {
        $response = $this->getJson('/betslips/lookup?code=');

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_lookup_returns_422_when_code_is_missing(): void
    {
        $response = $this->getJson('/betslips/lookup');

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_lookup_is_public(): void
    {
        $code = 'ABC-DEFG-HIJ';
        $this->makeBetslip($code);

        // No actingAs — guest.
        $response = $this->getJson('/betslips/lookup?code=' . $code);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function makeBetslip(string $code): Betslip
    {
        $seller = User::factory()->create([
            'name' => 'Seller',
            'code' => strtoupper(Str::random(8)),
        ]);

        return Betslip::create([
            'user_id'    => $seller->id,
            'code'       => $code,
            'price'      => 100,
            'total_odds' => 2.50,
            'status'     => 'pending',
            'remaining'  => 3,
            'is_winner'  => false,
        ]);
    }
}