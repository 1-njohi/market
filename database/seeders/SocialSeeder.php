<?php

namespace Database\Seeders;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\SellerMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\Cache::forget('leaderboard_top_10');
        $this->command->info("✓ Cleared leaderboard cache");
        $sellers = User::whereIn('code', ['WAZO-TANK-001', 'PHOE-PICK-002', 'DATA-DRVN-003', 'SHAR-SHOT-004', 'PROF-ESSR-005'])->get();
        $buyers  = User::whereIn('code', ['BRIA-KAMA-101', 'AISH-MWAN-102', 'JOHN-OTIE-103', 'GRAC-NJER-104', 'DENI-WANJ-105', 'MIKE-KIMA-106', 'SARA-WANJ-107', 'PETE-NJOR-108'])->get();

        // ─── Followers (raw DB so we don't depend on a Follower model) ───
        foreach ($buyers as $buyer) {
            foreach ($sellers->shuffle()->take(rand(1, 3)) as $seller) {
                DB::table('followers')->updateOrInsert(
                    ['follower_id' => $buyer->id, 'following_id' => $seller->id],
                    [
                        'is_notifications_enabled' => true,
                        'relationship_score' => rand(1, 5),
                        'followed_at' => Carbon::now()->subDays(rand(1, 60)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // ─── Seller Metrics ───
        foreach ($sellers as $seller) {
            $all = Betslip::where('user_id', $seller->id)->get();
            $settled = $all->whereIn('status', ['settled', 'completed']);

            $totalBetslips = $settled->count();
            $wonBetslips = $settled->where('is_winner', true)->count();
            $winRate = $totalBetslips > 0 ? round(($wonBetslips / $totalBetslips) * 100, 1) : 0;

            $wonAmount = $settled->where('is_winner', true)->sum(fn ($b) => $b->total_odds * $b->price);
            $lostAmount = $settled->where('is_winner', false)->sum('price');
            $staked = $wonAmount + $lostAmount;
            $roi = $staked > 0 ? round(($wonAmount / $staked) * 100, 1) : 0;

            $sold = BetslipUserPurchase::where('seller_id', $seller->id)->count();
            $revenue = BetslipUserPurchase::where('seller_id', $seller->id)->sum('purchase_price');
            $avgPrice = BetslipUserPurchase::where('seller_id', $seller->id)->avg('purchase_price') ?? 0;
            $avgOdds = $all->avg('total_odds') ?? 0;

            SellerMetric::updateOrCreate(
                ['user_id' => $seller->id],
                [
                    'win_rate' => $winRate,
                    'roi' => $roi,
                    'total_sold' => $sold,
                    'total_revenue' => round($revenue, 2),
                    'avg_price' => round($avgPrice, 2),
                    'avg_odds' => round($avgOdds, 2),
                    'avg_legs' => 3.5,
                    'follower_count' => DB::table('followers')->where('following_id', $seller->id)->count(),
                    'profile_views' => rand(50, 500),
                    'calculated_at' => now(),
                ]
            );
        }

        $this->command->info("✓ Seeded followers + seller metrics");
    }
}