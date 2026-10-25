<?php

namespace Tests\Feature\Seller;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/seller/settlements')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/seller/settlements')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/settlements')
            ->assertInertia(fn ($p) => $p->component('Seller/Settlements'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/settlements')
            ->assertInertia(fn ($p) => $p
                ->has('settlements.data')
                ->has('settlements.current_page')
                ->has('settlements.last_page')
                ->has('settlements.total')
            );
    }

    public function test_user_only_sees_settlements_from_their_own_betslips(): void
    {
        $alice  = User::factory()->create();
        $bob    = User::factory()->create();
        $buyer  = User::factory()->create();

        $this->makeSettledPurchase($bob, $buyer, 'won');

        $this->actingAs($alice)
            ->get('/seller/settlements')
            ->assertInertia(fn ($p) => $p->where('settlements.total', 0));
    }

    public function test_pending_purchases_are_excluded(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $this->makeSettledPurchase($seller, $buyer, 'pending');
        $this->makeSettledPurchase($seller, $buyer, 'won');

        $this->actingAs($seller)
            ->get('/seller/settlements')
            ->assertInertia(fn ($p) => $p->where('settlements.total', 1));
    }

    public function test_outcome_filter_narrows_results(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $this->makeSettledPurchase($seller, $buyer, 'won');
        $this->makeSettledPurchase($seller, $buyer, 'refunded');
        $this->makeSettledPurchase($seller, $buyer, 'voided');

        $this->actingAs($seller)
            ->get('/seller/settlements?outcome=won')
            ->assertInertia(fn ($p) => $p->where('settlements.total', 1));
    }

    private function makeSettledPurchase(
        User $seller,
        User $buyer,
        string $status,
    ): BetslipUserPurchase {
        $betslip = Betslip::create([
            'user_id'    => $seller->id,
            'code'       => strtoupper('T' . substr(uniqid(), -5)),
            'price'      => 100,
            'total_odds' => 2.00,
            'status'     => 'pending',
            'remaining'  => 5,
            'is_winner'  => $status === 'won',
            'caption'    => null,
        ]);

        return BetslipUserPurchase::create([
            'buyer_id'       => $buyer->id,
            'seller_id'      => $seller->id,
            'betslip_id'     => $betslip->id,
            'purchase_price' => 100,
            'total_odds'     => 2.00,
            'status'         => $status,
            'purchased_at'   => now(),
            'settled_at'     => in_array($status, ['won', 'refunded', 'voided'], true)
                ? now()
                : null,
        ]);
    }
}