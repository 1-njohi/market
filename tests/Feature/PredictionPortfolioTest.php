<?php

namespace Tests\Feature;

use App\Models\Prediction;
use App\Models\PredictionPointLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_page_loads_with_correct_stats(): void
    {
        // Create a "ranked" user (above our target)
        User::factory()->create(['prediction_points' => 500]);

        // Create our target user
        $user = User::factory()->create(['prediction_points' => 250]);

        // Log some points
        PredictionPointLog::create([
            'user_id' => $user->id,
            'amount' => 50,
            'balance_after' => 250,
            'type' => 'pick_win',
            'description' => 'Won Pick',
        ]);

        $response = $this->actingAs($user)->get(route('portfolio.index'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Profile/Predictions')
                ->where('stats.current_points', 250)
                ->where('stats.global_rank', 2) // Because one user has 500
                ->has('logs.data', 1)
        );
    }

    public function test_accuracy_calculation(): void
    {
        $user = User::factory()->create(['prediction_points' => 0]);

        // Create 3 won, 1 lost predictions
        foreach (range(1, 3) as $i) {
            Prediction::create([
                'user_id' => $user->id,
                'fixture_id' => 1,
                'market_id' => 1,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'confidence' => 3,
                'source_type' => 'betslip',
                'status' => 'won',
                'points_impact' => 10,
            ]);
        }
        Prediction::create([
            'user_id' => $user->id,
            'fixture_id' => 2,
            'market_id' => 1,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'confidence' => 3,
            'source_type' => 'betslip',
            'status' => 'lost',
            'points_impact' => -15,
        ]);

        $response = $this->actingAs($user)->get(route('portfolio.index'));

        // 3 wins / 4 total = 75%
        $response->assertInertia(
            fn($page) => $page
                ->where('stats.accuracy', 75.0)
        );
    }
}