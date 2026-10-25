<?php

namespace Tests\Feature\Seller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetslipsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/seller/betslips')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/seller/betslips')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/betslips')
            ->assertInertia(fn ($p) => $p->component('Seller/Betslips'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/betslips')
            ->assertInertia(fn ($p) => $p
                ->has('betslips.data')
                ->has('betslips.current_page')
                ->has('betslips.last_page')
                ->has('betslips.total')
            );
    }
}