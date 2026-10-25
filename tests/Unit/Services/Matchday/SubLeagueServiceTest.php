<?php

namespace Tests\Unit\Services\Matchday;

use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\SubLeagueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubLeagueServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SubLeagueService
    {
        return app(SubLeagueService::class);
    }

    public function test_generate_invite_code_produces_uppercase_alphanumeric(): void
    {
        $owner = User::factory()->create();

        $sub = $this->service()->create($owner, 'Test League', '2026-27');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{12}$/', $sub->invite_code);
    }

    public function test_generate_invite_code_is_unique_across_existing_rows(): void
    {
        config()->set('marketplace.matchday.max_sub_league_members', 20);

        $owner = User::factory()->create();

        // Pre-seed a row whose invite_code is exactly what our forced
        // random source will return on the first attempt. The service
        // must retry, not collide.
        $colliding = 'AAAAAAAAAAAA';
        SubLeague::create([
            'season'       => '2026-27',
            'name'         => 'Existing',
            'owner_id'     => $owner->id,
            'invite_code'  => $colliding,
            'max_members'  => 20,
        ]);

        // Force the first attempt to be the colliding value, then fall
        // back to parent random.
        $service = new class extends SubLeagueService {
            public int $calls = 0;
            protected function randomString(int $length): string
            {
                $this->calls++;
                return $this->calls === 1
                    ? str_repeat('A', $length)
                    : parent::randomString($length);
            }
        };

        $sub = $service->create($owner, 'Second', '2026-27');

        $this->assertNotSame($colliding, $sub->invite_code);
        $this->assertSame(2, $service->calls, 'Generator should retry once after a collision.');
    }

    public function test_is_full_returns_true_at_cap(): void
    {
        config()->set('marketplace.matchday.max_sub_league_members', 3);

        $owner = User::factory()->create();
        $sub   = $this->service()->create($owner, 'Small', '2026-27');

        // Owner is already a member (1). Two more to reach cap of 3.
        $this->service()->join(User::factory()->create(), $sub->invite_code);
        $this->service()->join(User::factory()->create(), $sub->invite_code);

        $this->assertTrue($this->service()->isFull($sub->fresh()));
    }

    public function test_is_full_reads_cap_from_config(): void
    {
        config()->set('marketplace.matchday.max_sub_league_members', 7);

        $owner = User::factory()->create();
        $sub   = $this->service()->create($owner, 'Mid', '2026-27');

        // The service must snapshot the config value at creation time.
        $this->assertSame(7, $sub->max_members);

        // And isFull must read max_members from the row, not re-read config.
        config()->set('marketplace.matchday.max_sub_league_members', 999);
        $this->assertFalse($this->service()->isFull($sub->fresh()));
    }
}