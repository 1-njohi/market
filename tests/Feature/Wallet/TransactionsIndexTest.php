<?php

namespace Tests\Feature\Wallet;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/wallet/transactions')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/wallet/transactions')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/wallet/transactions')
            ->assertInertia(fn ($p) => $p->component('Wallet/Transactions'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/wallet/transactions')
            ->assertInertia(fn ($p) => $p
                ->has('transactions.data')
                ->has('transactions.current_page')
                ->has('transactions.last_page')
                ->has('transactions.total')
                ->where('transactions.current_page', 1)
            );
    }

    public function test_user_only_sees_their_own_transactions(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $this->makeTransaction($bob, 'deposit');

        $this->actingAs($alice)
            ->get('/wallet/transactions')
            ->assertInertia(fn ($p) => $p->where('transactions.total', 0));
    }

    public function test_user_sees_their_own_transactions(): void
    {
        $alice = User::factory()->create();
        $this->makeTransaction($alice, 'deposit');

        $this->actingAs($alice)
            ->get('/wallet/transactions')
            ->assertInertia(fn ($p) => $p->where('transactions.total', 1));
    }

    public function test_type_filter_narrows_results(): void
    {
        $alice = User::factory()->create();
        $this->makeTransaction($alice, 'deposit');
        $this->makeTransaction($alice, 'withdrawal');

        $this->actingAs($alice)
            ->get('/wallet/transactions?type=deposit')
            ->assertInertia(fn ($p) => $p
                ->where('transactions.total', 1)
                ->where('transactions.data.0.type', 'deposit')
            );
    }

    public function test_type_filter_preserved_in_pagination_links(): void
    {
        $alice = User::factory()->create();
        for ($i = 0; $i < 25; $i++) {
            $this->makeTransaction($alice, 'deposit');
        }

        $response = $this->actingAs($alice)->get('/wallet/transactions?type=deposit');
        $response->assertInertia(fn ($p) => $p
            ->where('transactions.last_page', 2)
        );

        // The next page URL should carry the filter forward.
        $page = $response->viewData('page');
        $nextUrl = $page['props']['transactions']['next_page_url'] ?? null;
        $this->assertNotNull($nextUrl);
        $this->assertStringContainsString('type=deposit', $nextUrl);
    }

    private function makeTransaction(User $user, string $type): Transaction
    {
        return Transaction::create([
            'user_id'        => $user->id,
            'type'           => $type,
            'amount'         => 100,
            'balance_type'   => 'available',
            'balance_before' => 0,
            'balance_after'  => 100,
            'reference'      => (string) Str::uuid(),
            'status'         => 'completed',
        ]);
    }
}