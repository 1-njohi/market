<?php

namespace Tests\Unit\Models;

use App\Models\PredictionPointLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionPointLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_and_casts_correctly(): void
    {
        $user = User::factory()->create();

        $log = PredictionPointLog::create([
            'user_id' => $user->id,
            'amount' => 50,
            'balance_after' => 150,
            'type' => 'pick_win',
            'description' => 'Won Pick: Arsenal vs Chelsea (Home)',
        ]);

        $this->assertSame(50, $log->amount);
        $this->assertSame(150, $log->balance_after);
        $this->assertTrue($log->user->is($user));
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = PredictionPointLog::create([
            'user_id' => $user->id,
            'amount' => -75,
            'balance_after' => -75,
            'type' => 'pick_loss',
            'description' => 'Lost Pick',
        ]);

        $this->assertTrue($log->user->is($user));
    }
}