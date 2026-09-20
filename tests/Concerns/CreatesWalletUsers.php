<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Models\Wallet;

trait CreatesWalletUsers
{
    /**
     * @return array{0: User, 1: Wallet}
     */
    protected function makeUserWithWallet(float $balance = 0.0, float $escrow = 0.0): array
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id'         => $user->id,
            'balance'         => $balance,
            'escrow_balance'  => $escrow,
            'total_deposited' => $balance,
            'total_withdrawn' => 0,
            'currency'        => 'KES',
        ]);

        return [$user, $wallet];
    }
}