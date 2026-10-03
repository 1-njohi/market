<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PushStatusTest extends TestCase
{
    use DatabaseMigrations;

    public function test_status_returns_not_subscribed_for_user_with_no_subscriptions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/push/status');

        $response->assertOk();
        $response->assertJson([
            'subscribed' => false,
            'count'      => 0,
        ]);
    }

    public function test_status_returns_subscribed_for_user_with_a_subscription(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/abc123',
            'pubkey',
            'authtoken',
        );

        $response = $this->actingAs($user)->getJson('/push/status');

        $response->assertOk();
        $response->assertJson([
            'subscribed' => true,
            'count'      => 1,
        ]);
    }

    public function test_status_counts_multiple_devices(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://fcm/1', 'k1', 't1');
        $user->updatePushSubscription('https://fcm/2', 'k2', 't2');
        $user->updatePushSubscription('https://fcm/3', 'k3', 't3');

        $this->actingAs($user)
            ->getJson('/push/status')
            ->assertJson(['subscribed' => true, 'count' => 3]);
    }

    public function test_status_requires_auth(): void
    {
        $this->getJson('/push/status')->assertUnauthorized();
    }
}