<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_authenticated_user_can_subscribe(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'fake-p256dh', 'auth' => 'fake-auth'],
        ]);

        $response->assertNoContent();

        $this->assertSame(1, $user->pushSubscriptions()->count());

        $subscription = $user->pushSubscriptions()->first();
        $this->assertSame(
            'https://fcm.googleapis.com/fcm/send/abc123',
            $subscription->endpoint,
        );
        $this->assertSame('fake-p256dh', $subscription->public_key);
        $this->assertSame('fake-auth', $subscription->auth_token);
    }

    public function test_subscribe_requires_auth(): void
    {
        $this->postJson('/push/subscribe', [
            'endpoint' => 'x',
            'keys' => ['p256dh' => 'y', 'auth' => 'z'],
        ])->assertUnauthorized();
    }

    public function test_subscribe_validates_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/push/subscribe', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_resubscribing_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'old', 'auth' => 'old'],
        ];

        $this->actingAs($user)->postJson('/push/subscribe', $payload);
        $payload['keys']['p256dh'] = 'new';
        $this->actingAs($user)->postJson('/push/subscribe', $payload);

        $this->assertSame(1, $user->pushSubscriptions()->count());
        $this->assertSame('new', $user->pushSubscriptions()->first()->public_key);
    }

    public function test_user_can_unsubscribe(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://fcm...', 'key', 'token');

        $this->actingAs($user)->deleteJson('/push/subscribe', [
            'endpoint' => 'https://fcm...',
        ])->assertNoContent();

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }
}