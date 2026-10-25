<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsIndexTest extends TestCase
{
    use DatabaseMigrations;

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(Str::random(8)),
        ]);
    }

    private function makeNotification(
        User $user,
        string $type,
        array $extra = [],
        ?string $readAt = null,
    ): void {
        $user->notifications()->create([
            'id'      => (string) Str::uuid(),
            'type'    => 'App\\Notifications\\GenericNotification',
            'data'    => array_merge([
                'type'  => $type,
                'title' => 'Test title',
                'body'  => 'Test body',
            ], $extra),
            'read_at' => $readAt,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Page (Inertia) behaviour — GET /notifications
    // ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk();
    }

    public function test_page_renders_notifications_component(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->get('/notifications')
            ->assertInertia(fn ($p) => $p->component('Notifications'));
    }

    public function test_paginator_shape_is_exposed(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->get('/notifications')
            ->assertInertia(fn ($p) => $p
                ->has('notifications.data')
                ->has('notifications.current_page')
                ->has('notifications.last_page')
                ->has('notifications.total')
            );
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        $alice = $this->makeUser();
        $bob   = $this->makeUser();

        $this->makeNotification($bob, 'betslip_won');

        $this->actingAs($alice)
            ->get('/notifications')
            ->assertInertia(fn ($p) => $p->where('notifications.total', 0));
    }

    public function test_user_sees_their_own_notifications(): void
    {
        $alice = $this->makeUser();
        $this->makeNotification($alice, 'betslip_won');

        $this->actingAs($alice)
            ->get('/notifications')
            ->assertInertia(fn ($p) => $p->where('notifications.total', 1));
    }

    public function test_unread_filter_narrows_results(): void
    {
        $user = $this->makeUser();
        $this->makeNotification($user, 'betslip_won');
        $this->makeNotification($user, 'contest_settled', [], now()->toDateTimeString());

        $this->actingAs($user)
            ->get('/notifications?status=unread')
            ->assertInertia(fn ($p) => $p->where('notifications.total', 1));
    }

    public function test_type_filter_narrows_results(): void
    {
        $user = $this->makeUser();
        $this->makeNotification($user, 'betslip_won');
        $this->makeNotification($user, 'contest_settled');
        $this->makeNotification($user, 'referral_reward');

        $this->actingAs($user)
            ->get('/notifications?type=betslip')
            ->assertInertia(fn ($p) => $p->where('notifications.total', 1));
    }

    public function test_unread_count_is_exposed(): void
    {
        $user = $this->makeUser();
        $this->makeNotification($user, 'betslip_won');
        $this->makeNotification($user, 'contest_settled', [], now()->toDateTimeString());

        $this->actingAs($user)
            ->get('/notifications')
            ->assertInertia(fn ($p) => $p->where('unread_count', 1));
    }

    // ─────────────────────────────────────────────────────────────
    // Bell feed (JSON) — GET /notifications/feed
    // ─────────────────────────────────────────────────────────────

    public function test_json_endpoint_returns_success_shape(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->getJson('/notifications/feed')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'unread_count',
                'notifications' => [],
            ]);
    }

    public function test_json_endpoint_lists_notifications(): void
    {
        $user = $this->makeUser();
        $this->makeNotification($user, 'betslip_won');

        $this->actingAs($user)
            ->getJson('/notifications/feed')
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');
    }
}