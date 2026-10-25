<?php

namespace Tests\Unit\Services;

use App\Models\PredictionPointLog;
use App\Models\User;
use App\Services\PredictionPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionPointServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PredictionPointService
    {
        return app(PredictionPointService::class);
    }

    public function test_adjusting_points_credits_user_and_writes_log(): void
    {
        $user = User::factory()->create(['prediction_points' => 0]);

        $this->service()->adjust($user, 50, 'pick_win', 'Won Pick');

        $this->assertSame(50, $user->fresh()->prediction_points);
        $this->assertSame(1, PredictionPointLog::count());

        $log = PredictionPointLog::first();
        $this->assertSame(50, $log->amount);
        $this->assertSame(50, $log->balance_after);
        $this->assertSame('pick_win', $log->type);
    }

    public function test_adjusting_points_debits_user_going_negative(): void
    {
        $user = User::factory()->create(['prediction_points' => 0]);

        $this->service()->adjust($user, -75, 'pick_loss', 'Lost Pick');

        $this->assertSame(-75, $user->fresh()->prediction_points);

        $log = PredictionPointLog::first();
        $this->assertSame(-75, $log->amount);
        $this->assertSame(-75, $log->balance_after);
    }

    public function test_zero_amount_is_no_op(): void
    {
        $user = User::factory()->create(['prediction_points' => 100]);

        $this->service()->adjust($user, 0, 'pick_win', 'Void Pick');

        $this->assertSame(100, $user->fresh()->prediction_points);
        $this->assertSame(0, PredictionPointLog::count());
    }

    public function test_source_is_linked_polymorphically(): void
    {
        $user = User::factory()->create(['prediction_points' => 0]);
        $prediction = \App\Models\Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => $this->makeFixture()->id,
            'market_id' => $this->makeMarket()->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 3,
            'source_type' => 'betslip',
        ]);

        $this->service()->adjust($user, 50, 'pick_win', 'Won', $prediction);

        $log = PredictionPointLog::first();
        $this->assertSame(\App\Models\Prediction::class, $log->source_type);
        $this->assertSame($prediction->id, $log->source_id);
    }

    public function test_score_prediction_win(): void
    {
        $result = $this->service()->scorePrediction(
            confidence: 5,
            odds: 2.00,
            isCorrect: true,
            isVoid: false,
        );

        $this->assertSame(50, $result['points']);
        $this->assertSame('won', $result['status']);
    }

    public function test_score_prediction_loss(): void
    {
        $result = $this->service()->scorePrediction(
            confidence: 5,
            odds: 10.00,
            isCorrect: false,
            isVoid: false,
        );

        $this->assertSame(-675, $result['points']);
        $this->assertSame('lost', $result['status']);
    }

    public function test_score_prediction_void(): void
    {
        $result = $this->service()->scorePrediction(
            confidence: 5,
            odds: 2.00,
            isCorrect: false,
            isVoid: true,
        );

        $this->assertSame(0, $result['points']);
        $this->assertSame('void', $result['status']);
    }

    private function makeFixture(): \App\Models\Fixture
    {
        \App\Models\League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39,
            'sport_id' => 1,
            'name' => 'PL',
            'country_id' => 1,
            'country' => 'England',
            'season' => 2026,
        ]);
        return \App\Models\Fixture::factory()->create(['league_id' => 39]);
    }

    private function makeMarket(): \App\Models\Market
    {
        return \App\Models\Market::factory()->create();
    }
}