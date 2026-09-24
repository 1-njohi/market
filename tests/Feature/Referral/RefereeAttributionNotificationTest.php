<?php

namespace Tests\Feature\Referral;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefereeAttributionNotificationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_referee_gets_notification_when_attributed(): void
    {
        $referrer = $this->makeUser('Denis Wanjohi');

        $this->post('/register', $this->payload([
            'referral_code' => $referrer->referral_code,
        ]));

        $referee = User::where('email', 'buyer@test.local')->firstOrFail();

        $row = DB::table('notifications')
            ->where('notifiable_id', $referee->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"referral_attributed"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('referral_attributed', $data['type']);
        $this->assertSame($referrer->name, $data['referrer_name']);
        $this->assertStringContainsString($referrer->name, $data['body']);
    }

    public function test_no_notification_when_registration_has_no_referral(): void
    {
        $this->post('/register', $this->payload());

        $referee = User::where('email', 'buyer@test.local')->firstOrFail();

        $count = DB::table('notifications')
            ->where('notifiable_id', $referee->id)
            ->where('data', 'like', '%"type":"referral_attributed"%')
            ->count();

        $this->assertSame(0, $count);
    }

    public function test_no_notification_when_code_is_invalid(): void
    {
        $this->post('/register', $this->payload([
            'referral_code' => 'DOES-NOT-EXIST',
        ]));

        $referee = User::where('email', 'buyer@test.local')->firstOrFail();

        $count = DB::table('notifications')
            ->where('notifiable_id', $referee->id)
            ->where('data', 'like', '%"type":"referral_attributed"%')
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