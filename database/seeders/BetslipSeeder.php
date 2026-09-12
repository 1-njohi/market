<?php

namespace Database\Seeders;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Fixture;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BetslipLostNotification;
use App\Notifications\BetslipWonNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BetslipSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::whereIn('code', ['WAZO-TANK-001', 'PHOE-PICK-002', 'DATA-DRVN-003', 'SHAR-SHOT-004', 'PROF-ESSR-005'])->get();
        $buyers  = User::whereIn('code', ['BRIA-KAMA-101', 'AISH-MWAN-102', 'JOHN-OTIE-103', 'GRAC-NJER-104', 'DENI-WANJ-105', 'MIKE-KIMA-106', 'SARA-WANJ-107', 'PETE-NJOR-108'])->get();

        if ($sellers->isEmpty() || $buyers->isEmpty()) {
            $this->command->error("Run TestUserSeeder first.");
            return;
        }

        $settledFixtures  = Fixture::where('status_short', 'FT')->get();
        $upcomingFixtures = Fixture::where('status_short', 'NS')->get();

        // [seller_idx, fixture_pool, leg_count, status, is_winner]
        $config = [
            [0, 'settled',  3, 'settled', true],
            [0, 'settled',  2, 'settled', false],
            [1, 'settled',  4, 'settled', true],
            [1, 'settled',  3, 'settled', false],
            [2, 'settled',  2, 'settled', true],
            [2, 'settled',  3, 'settled', true],
            [3, 'settled',  2, 'settled', false],
            [4, 'settled',  3, 'settled', true],
            [0, 'upcoming', 3, 'pending',  null],
            [1, 'upcoming', 4, 'pending',  null],
            [2, 'upcoming', 2, 'pending',  null],
            [3, 'upcoming', 3, 'pending',  null],
            [4, 'upcoming', 2, 'pending',  null],
            [0, 'upcoming', 5, 'pending',  null],
            [1, 'upcoming', 3, 'pending',  null],
            [2, 'upcoming', 4, 'underway', null],
            [3, 'upcoming', 2, 'underway', null],
            [4, 'upcoming', 3, 'pending',  null],
            [0, 'upcoming', 2, 'pending',  null],
            [1, 'upcoming', 3, 'pending',  null],
        ];

        $count = 0;

        foreach ($config as [$sellerIdx, $pool, $legs, $status, $isWinner]) {
            $seller = $sellers[$sellerIdx];
            $fixtures = $pool === 'settled' ? $settledFixtures : $upcomingFixtures;
            if ($fixtures->isEmpty()) continue;

            $odds = collect();
            foreach ($fixtures->shuffle()->take($legs) as $fixture) {
                $pick = $fixture->odds()
                    ->whereIn('market_id', [1, 5, 8, 12, 13, 21])
                    ->get()
                    ->shuffle()
                    ->first();
                if ($pick) $odds->push($pick);
            }
            if ($odds->isEmpty()) continue;

            $totalOdds = round($odds->reduce(fn ($c, $o) => $c * (float) $o->odd, 1), 2);
            $price = collect([20, 50, 80, 100, 120, 150, 200, 250])->random();
            $createdAt = $pool === 'settled'
                ? Carbon::now()->subDays(rand(3, 30))
                : Carbon::now()->subHours(rand(1, 72));

            $betslip = Betslip::create([
                'id'             => (string) Str::uuid(),
                'user_id'        => $seller->id,
                'code'           => $this->uniqueCode(),
                'total_odds'     => $totalOdds,
                'price'          => $price,
                'status'         => $status,
                'priority_score' => rand(1, 10),
                'caption'        => $this->caption(),
                'remaining'      => rand(3, 10),
                'is_winner'      => $isWinner ?? false,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ]);

            foreach ($odds as $odd) {
                $pivotStatus = $status === 'settled' ? $odd->status : 'pending';
                $betslip->odds()->attach($odd->id, [
                    'status' => $pivotStatus,
                    'odd_value_at_time' => $odd->odd,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            // Purchases
            if ($status !== 'pending') {
                foreach ($buyers->shuffle()->take(rand(1, 4)) as $buyer) {
                    $purchaseStatus = match ($status) {
                        'settled' => $isWinner ? 'won' : 'refunded',
                        default   => 'pending',
                    };

                    BetslipUserPurchase::create([
                        'betslip_id'        => $betslip->id,
                        'buyer_id'          => $buyer->id,
                        'seller_id'         => $seller->id,
                        'purchase_price'    => $price,
                        'total_odds'        => $totalOdds,
                        'status'            => $purchaseStatus,
                        'payment_method'    => 'wallet',
                        'payment_reference' => 'PUR-' . strtoupper(Str::random(8)),
                        'purchased_at'      => $createdAt->copy()->addMinutes(rand(5, 120)),
                    ]);

                    $this->ledger($buyer, $seller, $price, $betslip, $purchaseStatus);

                    if ($status === 'settled') {
                        $note = $isWinner
                            ? new BetslipWonNotification($betslip)
                            : new BetslipLostNotification($betslip);
                        $buyer->notify($note);
                        $seller->notify($note);
                    }
                }
            } elseif (rand(0, 1)) {
                // Some pending betslips get one purchase
                $buyer = $buyers->random();
                BetslipUserPurchase::create([
                    'betslip_id'        => $betslip->id,
                    'buyer_id'          => $buyer->id,
                    'seller_id'         => $seller->id,
                    'purchase_price'    => $price,
                    'total_odds'        => $totalOdds,
                    'status'            => 'pending',
                    'payment_method'    => 'wallet',
                    'payment_reference' => 'PUR-' . strtoupper(Str::random(8)),
                    'purchased_at'      => $createdAt->copy()->addMinutes(rand(5, 120)),
                ]);
            }

            $count++;
        }

        $this->command->info("✓ Seeded {$count} betslips with purchases");
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));
        } while (Betslip::where('code', $code)->exists());
        return $code;
    }

    private function caption(): string
    {
        return collect([
            'Solid value on this one — defence is shaky and the stats back it.',
            'Home team has been dominating set pieces all season.',
            'Both teams are due for goals — expect an open game.',
            'Over is my call. This one historically runs high.',
            'I have been tracking this fixture for weeks.',
            'Form guide points to a clean home win here.',
        ])->random();
    }

    private function ledger(User $buyer, User $seller, float $price, Betslip $betslip, string $status): void
    {
        $ref = $betslip->code . '-' . $betslip->created_at->timestamp;

        Transaction::create([
            'user_id' => $buyer->id, 'type' => 'purchase', 'amount' => -$price,
            'balance_before' => 0, 'balance_after' => 0, 'status' => 'completed',
            'reference' => $ref . '-BUY',
            'description' => "Purchase of betslip #{$betslip->code}",
            'completed_at' => $betslip->created_at,
        ]);

        Transaction::create([
            'user_id' => $seller->id, 'type' => 'pending_payout', 'amount' => $price,
            'balance_before' => 0, 'balance_after' => 0, 'status' => 'completed',
            'reference' => $ref . '-SELL',
            'description' => "Pending payout for betslip #{$betslip->code}",
            'completed_at' => $betslip->created_at,
        ]);

        if ($status === 'won') {
            $fee = round($price * 0.25, 2);
            $net = round($price - $fee, 2);

            Transaction::create([
                'user_id' => $seller->id, 'type' => 'fee', 'amount' => -$fee,
                'balance_before' => 0, 'balance_after' => 0, 'status' => 'completed',
                'reference' => $ref . '-FEE',
                'description' => "Platform fee for betslip #{$betslip->code}",
                'completed_at' => now(),
            ]);

            Transaction::create([
                'user_id' => $seller->id, 'type' => 'payout', 'amount' => $net,
                'balance_before' => 0, 'balance_after' => 0, 'status' => 'completed',
                'reference' => $ref . '-PAYOUT',
                'description' => "Net payout for betslip #{$betslip->code}",
                'completed_at' => now(),
            ]);
        } elseif ($status === 'refunded') {
            Transaction::create([
                'user_id' => $buyer->id, 'type' => 'refund', 'amount' => $price,
                'balance_before' => 0, 'balance_after' => 0, 'status' => 'completed',
                'reference' => $ref . '-REFUND',
                'description' => "Refund for losing betslip #{$betslip->code}",
                'completed_at' => now(),
            ]);
        }
    }
}