<?php

namespace Tests\Feature\Referral;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferrerSignupNotificationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_referrer_gets_notification_when_referee_signs_up(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->post('/register', $this->payload([
            'name' => 'Test Buyer',
            'referral_code' => $referrer->referral_code,
        ]));

        $referee = User::where('email', 'buyer@test.local')->firstOrFail();

        $row = DB::table('notifications')
            ->where('notifiable_id', $referrer->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"referral_signup"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('referral_signup', $data['type']);
        $this->assertSame($referee->id, $data['referee_id']);
        $this->assertSame('Test Buyer', $data['referee_name']);
        $this->assertStringContainsString('Test Buyer', $data['body']);
    }

    public function test_referrer_gets_no_notification_when_no_referral(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->post('/register', $this->payload());

        $count = DB::table('notifications')
            ->where('notifiable_id', $referrer->id)
            ->where('data', 'like', '%"type":"referral_signup"%')
            ->count();

        $this->assertSame(0, $count);
    }

    public function test_referrer_gets_no_notification_when_code_is_invalid(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->post('/register', $this->payload([
            'referral_code' => 'DOES-NOT-EXIST',
        ]));

        $count = DB::table('notifications')
            ->where('notifiable_id', $referrer->id)
            ->where('data', 'like', '%"type":"referral_signup"%')
            ->count();

        $this->assertSame(0, $count);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.local',
            'phone' => '254700000000',
            'country_code' => 'KE',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }
}