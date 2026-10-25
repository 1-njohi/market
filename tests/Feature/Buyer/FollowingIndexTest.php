<?php

namespace Tests\Feature\Buyer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/buyer/following')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/buyer/following')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/buyer/following')
            ->assertInertia(fn ($p) => $p->component('Buyer/Following'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/buyer/following')
            ->assertInertia(fn ($p) => $p
                ->has('following.data')
                ->has('following.current_page')
                ->has('following.last_page')
                ->has('following.total')
            );
    }

    public function test_buyer_only_sees_sellers_they_follow(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $seller = User::factory()->create();

        $bob->follow($seller);

        $this->actingAs($alice)
            ->get('/buyer/following')
            ->assertInertia(fn ($p) => $p->where('following.total', 0));
    }

    public function test_buyer_sees_their_own_follows(): void
    {
        $alice = User::factory()->create();
        $seller = User::factory()->create();

        $alice->follow($seller);

        $this->actingAs($alice)
            ->get('/buyer/following')
            ->assertInertia(fn ($p) => $p->where('following.total', 1));
    }
}