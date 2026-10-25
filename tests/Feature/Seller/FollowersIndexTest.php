<?php

namespace Tests\Feature\Seller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/seller/followers')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/seller/followers')->assertOk();
    }

    public function test_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/followers')
            ->assertInertia(fn ($p) => $p->component('Seller/Followers'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/seller/followers')
            ->assertInertia(fn ($p) => $p
                ->has('followers.data')
                ->has('followers.current_page')
                ->has('followers.last_page')
                ->has('followers.total')
            );
    }
}