<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProfileBioTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_can_save_a_bio(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => 'KPL and La Liga specialist. 6 years tracking Kenyan football.',
        ]);

        $response->assertRedirect('/settings/profile');

        $this->assertSame(
            'KPL and La Liga specialist. 6 years tracking Kenyan football.',
            $user->fresh()->bio,
        );
    }

    public function test_bio_can_be_cleared(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['bio' => 'Old bio'])->save();

        $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => '',
        ]);

        $this->assertNull($user->fresh()->bio);
    }

    public function test_bio_has_a_max_length(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => str_repeat('a', 501),
        ]);

        $response->assertSessionHasErrors('bio');
        $this->assertNull($user->fresh()->bio);
    }

    public function test_bio_rejects_html_tags(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => '<script>alert(1)</script>Hello',
        ]);

        // The tag should be stripped, not stored, or the request should fail.
        // We strip silently (Laravel's default sanitization via strip_tags).
        $fresh = $user->fresh()->bio;

        $this->assertStringNotContainsString('<script>', (string) $fresh);
        $this->assertStringContainsString('Hello', (string) $fresh);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}