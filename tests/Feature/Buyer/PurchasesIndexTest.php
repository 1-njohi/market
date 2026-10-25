<?php

namespace Tests\Feature\Buyer;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchasesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/buyer/purchases')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/buyer/purchases')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/buyer/purchases')
            ->assertInertia(fn ($p) => $p->component('Buyer/Purchases'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/buyer/purchases')
            ->assertInertia(fn ($p) => $p
                ->has('purchases.data')
                ->has('purchases.current_page')
                ->has('purchases.last_page')
                ->has('purchases.total')
            );
    }

    public function test_user_only_sees_their_own_purchases(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $seller = User::factory()->create();

        $this->makePurchase($bob, $seller);

        $this->actingAs($alice)
            ->get('/buyer/purchases')
            ->assertInertia(fn ($p) => $p->where('purchases.total', 0));
    }

    public function test_status_filter_narrows_results(): void
    {
        $alice = User::factory()->create();
        $seller = User::factory()->create();

        $this->makePurchase($alice, $seller, 'pending');
        $this->makePurchase($alice, $seller, 'won');
        $this->makePurchase($alice, $seller, 'won');

        $this->actingAs($alice)
            ->get('/buyer/purchases?status=won')
            ->assertInertia(fn ($p) => $p->where('purchases.total', 2));
    }

    public function test_settled_filter_matches_won_refunded_voided(): void
    {
        $alice = User::factory()->create();
        $seller = User::factory()->create();

        $this->makePurchase($alice, $seller, 'pending');
        $this->makePurchase($alice, $seller, 'won');
        $this->makePurchase($alice, $seller, 'refunded');

        $this->actingAs($alice)
            ->get('/buyer/purchases?status=settled')
            ->assertInertia(fn ($p) => $p->where('purchases.total', 2));
    }

    private function makePurchase(
        User $buyer,
        User $seller,
        string $status = 'pending',
    ): BetslipUserPurchase {
        $betslip = Betslip::create([
            'user_id'    => $seller->id,
            'code'       => strtoupper('T' . substr(uniqid(), -5)),
            'price'      => 100,
            'total_odds' => 2.00,
            'status'     => 'pending',
            'remaining'  => 5,
            'is_winner'  => false,
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
        ]);
    }
}