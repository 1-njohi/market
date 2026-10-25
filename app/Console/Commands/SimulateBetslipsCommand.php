<?php

namespace App\Console\Commands;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulateBetslipsCommand extends Command
{
    protected $signature = 'betslips:simulate
                            {--slips=78 : Number of betslips to create}
                            {--users=20 : Total number of non-admin users to draw from}
                            {--min-legs=2 : Minimum legs per slip}
                            {--max-legs=3 : Maximum legs per slip}
                            {--min-price=40 : Minimum betslip price}
                            {--max-price=200 : Maximum betslip price}
                            {--min-purchases=6 : Minimum purchases per slip}
                            {--max-purchases=16 : Maximum purchases per slip}
                            {--seed-balance=2000 : Starting wallet balance per user}
                            {--clear : Delete existing simulated betslips before running}';

    protected $description = 'Seeds betslips and real purchases, with explicit Sellers and Buyers.';

    public function handle(WalletService $wallet): int
    {
        $slipCount     = (int) $this->option('slips');
        $userCount     = (int) $this->option('users');
        $minLegs       = (int) $this->option('min-legs');
        $maxLegs       = (int) $this->option('max-legs');
        $minPrice      = (int) $this->option('min-price');
        $maxPrice      = (int) $this->option('max-price');
        $minPurchases  = (int) $this->option('min-purchases');
        $maxPurchases  = (int) $this->option('max-purchases');
        $seedBalance   = (int) $this->option('seed-balance');

        // ── 1. Load users and split by role ────────────────────────
        $allUsers = User::where('is_admin', false)
            ->orderBy('id')
            ->limit($userCount)
            ->get();

        if ($allUsers->count() < 4) {
            $this->error("Need at least 4 non-admin users. Found: {$allUsers->count()}");
            return self::FAILURE;
        }

        $half = (int) floor($allUsers->count() / 2);
        $sellers = $allUsers->take($half);
        $buyers  = $allUsers->slice($half);

        $this->info("Loaded {$allUsers->count()} users. Split into roles:");
        $this->newLine();
        $this->info("── SELLERS ({$sellers->count()}) ─────────────────────────────");
        foreach ($sellers as $u) {
            $this->line("  [{$u->id}] {$u->email}");
        }
        $this->newLine();
        $this->info("── BUYERS ({$buyers->count()}) ──────────────────────────────");
        foreach ($buyers as $u) {
            $this->line("  [{$u->id}] {$u->email}");
        }
        $this->newLine();

        // ── 2. Optional cleanup ────────────────────────────────────
        if ($this->option('clear')) {
            $ids = $allUsers->pluck('id')->all();
            $slipIds = DB::table('betslips')->whereIn('user_id', $ids)->pluck('id');

            if ($slipIds->isNotEmpty()) {
                BetslipUserPurchase::whereIn('betslip_id', $slipIds)->delete();
                DB::table('betslip_odd')->whereIn('betslip_id', $slipIds)->delete();
                DB::table('betslips')->whereIn('id', $slipIds)->delete();
            }

            $this->warn("Cleared existing betslips for these {$allUsers->count()} users.");
        }

        // ── 3. Fund wallets (uses the real credit() so escrow is consistent) ──
        $this->info("Funding wallets to {$seedBalance} KES each...");

        foreach ($allUsers as $u) {
            $currentBalance = (float) $this->getBalance($u);
            $needed = $seedBalance - $currentBalance;

            if ($needed > 0) {
                $wallet->credit(
                    user: $u,
                    amount: $needed,
                    type: 'deposit',
                    reference: 'SIM-' . Str::random(10),
                    description: 'Simulation seed deposit',
                );
            }
        }

        // ── 4. Find candidate legs (2-3 outcome markets) ───────────
        $this->info("Finding fixtures in low-option markets...");

        $candidateFixtures = Fixture::where('status_short', 'NS')
            ->where('date', '>', now())
            ->whereHas('Odds', fn ($q) => $q->where('status', 'pending'))
            ->limit(60)
            ->get();

        if ($candidateFixtures->count() < 5) {
            $this->error("Not enough upcoming fixtures with pending odds. Found {$candidateFixtures->count()}.");
            return self::FAILURE;
        }

        $legOptionsByFixture = [];
        foreach ($candidateFixtures as $fx) {
            $oddsByMarket = $fx->Odds()->where('status', 'pending')->get()->groupBy('market_id');
            foreach ($oddsByMarket as $marketId => $odds) {
                $distinctValues = $odds->pluck('value')->unique()->values();
                if ($distinctValues->count() < 2 || $distinctValues->count() > 3) {
                    continue;
                }
                $legOptionsByFixture[] = [
                    'fixture_id' => $fx->id,
                    'market_id'  => (int) $marketId,
                    'options'    => $distinctValues->map(fn ($v) => [
                        'value' => $v,
                        'odd'   => (float) $odds->firstWhere('value', $v)->odd,
                    ])->values()->all(),
                ];
            }
        }

        if (count($legOptionsByFixture) < 5) {
            $this->error('Not enough 2-3 option markets. Found ' . count($legOptionsByFixture));
            return self::FAILURE;
        }

        // ── 5. Create betslips and purchases ───────────────────────
        $this->info("Creating {$slipCount} betslips...");
        $purchasesMade = 0;

        for ($i = 0; $i < $slipCount; $i++) {
            $seller = $sellers->random();

            $legCount = rand($minLegs, $maxLegs);
            $legs = collect($legOptionsByFixture)->shuffle()->take($legCount)->values();

            $totalOdds = 1.0;
            $legData = [];
            foreach ($legs as $leg) {
                $pick = $leg['options'][array_rand($leg['options'])];
                $totalOdds *= $pick['odd'];
                $legData[] = [
                    'fixture_id' => $leg['fixture_id'],
                    'market_id'  => $leg['market_id'],
                    'odd_value'  => $pick['value'],
                    'odd'        => $pick['odd'],
                ];
            }

            $price = rand($minPrice, $maxPrice);

            $slip = Betslip::create([
                'user_id'        => $seller->id,
                'code'           => strtoupper(Str::random(10)),
                'total_odds'     => round($totalOdds, 2),
                'price'          => $price,
                'status'         => 'pending',
                'remaining'      => $price,
                'is_winner'      => false,
                'is_sellable'    => true,
                'is_delisted'    => false,
                'priority_score' => rand(1, 100),
            ]);

            foreach ($legData as $leg) {
                $oddId = Odd::where('fixture_id', $leg['fixture_id'])
                    ->where('market_id', $leg['market_id'])
                    ->where('value', $leg['odd_value'])
                    ->value('id');

                if (!$oddId) continue;

                DB::table('betslip_odd')->insert([
                    'betslip_id'        => $slip->id,
                    'odd_id'            => $oddId,
                    'status'            => 'pending',
                    'odd_value_at_time' => $leg['odd'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            // ── 6. Generate purchases ─────────────────────────────
            $purchaseCount = rand($minPurchases, $maxPurchases);

            // Buyers buy; sellers buy each other's slips (not their own).
            $pool = $buyers->merge($sellers->where('id', '!=', $seller->id));
            $chosenBuyers = $pool->shuffle()->take($purchaseCount);

            foreach ($chosenBuyers as $buyer) {
                // Check the buyer has enough available balance.
                if (! $wallet->hasSufficientBalance($buyer, $price)) {
                    continue;
                }

                DB::transaction(function () use ($buyer, $slip, $price, $wallet) {
                    $purchase = BetslipUserPurchase::create([
                        'betslip_id'        => $slip->id,
                        'buyer_id'          => $buyer->id,
                        'seller_id'         => $slip->user_id,
                        'purchase_price'    => $price,
                        'total_odds'        => $slip->total_odds,
                        'status'            => 'pending',
                        'payment_method'    => 'wallet',
                        'payment_reference' => 'SIM-' . Str::random(10),
                        'purchased_at'      => now(),
                    ]);

                    // Move money through the real escrow flow:
                    // buyer's balance → seller's escrow.
                    $wallet->hold(
                        user: $buyer,
                        amount: $price,
                        type: 'betslip_purchase',
                        description: "Purchase of betslip #{$slip->code}",
                    );

                    $slip->decrement('remaining', $price);
                });

                $purchasesMade++;
            }
        }

        // ── 7. Report ──────────────────────────────────────────────
        $this->newLine();
        $this->info('Simulation complete.');
        $this->line("Betslips created: {$slipCount}");
        $this->line("Purchases created: {$purchasesMade}");
        $this->line("Avg purchases per slip: " . round($purchasesMade / max(1, $slipCount), 1));

        $this->newLine();
        $this->info('Final wallet balances:');

        $this->line("── SELLERS ──────────────────────────────");
        foreach ($sellers as $u) {
            $balance  = (float) $this->getBalance($u);
            $escrow   = (float) $wallet->getEscrowBalance($u);
            $this->line(sprintf(
                '  [%d] %s — available %.2f · escrow %.2f KES',
                $u->id, $u->email, $balance, $escrow,
            ));
        }

        $this->line("── BUYERS ───────────────────────────────");
        foreach ($buyers as $u) {
            $balance = (float) $this->getBalance($u);
            $escrow  = (float) $wallet->getEscrowBalance($u);
            $this->line(sprintf(
                '  [%d] %s — available %.2f · escrow %.2f KES',
                $u->id, $u->email, $balance, $escrow,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Reads the wallet balance without going through the service, so
     * we don't trigger any side-effects (like transaction rows).
     */
    private function getBalance(User $user): float
    {
        return (float) (Wallet::where('user_id', $user->id)->value('balance') ?? 0);
    }
}