<?php

namespace Tests\Feature\Wallet;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesWalletUsers;

    public function test_settle_escrow_to_seller_moves_money_across_three_wallets(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet(balance: 0, escrow: 0);
        [$platform] = $this->makeUserWithWallet(balance: 0, escrow: 0);

        app(WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: 200.00,
            fee: 20.00,
        );

        $buyerWallet = Wallet::where('user_id', $buyer->id)->firstOrFail();
        $sellerWallet = Wallet::where('user_id', $seller->id)->firstOrFail();
        $platformWallet = Wallet::where('user_id', $platform->id)->firstOrFail();

        // Buyer's escrow is drained — money left the buyer's account.
        $this->assertSame(0.00, (float) $buyerWallet->escrow_balance);
        $this->assertSame(0.00, (float) $buyerWallet->balance);

        // Seller received the gross, then paid the fee. Net = 180.
        $this->assertSame(180.00, (float) $sellerWallet->balance);
        $this->assertSame(0.00, (float) $sellerWallet->escrow_balance);

        // Platform received the fee.
        $this->assertSame(20.00, (float) $platformWallet->balance);
    }

    public function test_settle_escrow_to_seller_writes_a_complete_ledger(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();

        app(WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: 200.00,
            fee: 20.00,
        );

        // Buyer: one row, escrow debited.
        $buyerRows = Transaction::where('user_id', $buyer->id)->get();
        $this->assertCount(1, $buyerRows);
        $this->assertSame('escrow', $buyerRows->first()->balance_type);
        $this->assertSame(-200.00, (float) $buyerRows->first()->amount);

        // Seller: two rows — gross credit, fee debit.
        $sellerRows = Transaction::where('user_id', $seller->id)->orderBy('id')->get();
        $this->assertCount(2, $sellerRows);
        $this->assertSame(200.00, (float) $sellerRows[0]->amount);   // gross in
        $this->assertSame(-20.00, (float) $sellerRows[1]->amount);   // fee out
        $this->assertSame('available', $sellerRows[0]->balance_type);
        $this->assertSame('available', $sellerRows[1]->balance_type);

        // Platform: one row, fee credited.
        $platformRows = Transaction::where('user_id', $platform->id)->get();
        $this->assertCount(1, $platformRows);
        $this->assertSame(20.00, (float) $platformRows->first()->amount);
        $this->assertSame('available', $platformRows->first()->balance_type);
    }

    public function test_settle_escrow_to_seller_throws_when_buyer_escrow_is_insufficient(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 50.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();

        $this->expectException(\App\Exceptions\InsufficientBalanceException::class);

        app(WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: 200.00,
            fee: 20.00,
        );

        // And nothing moved.
        $this->assertSame(50.00, (float) Wallet::where('user_id', $buyer->id)->firstOrFail()->escrow_balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);
    }

    public function test_refund_escrow_returns_money_to_available(): void
    {
        [$buyer, $buyerWallet] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);

        app(WalletService::class)->refundEscrow(
            user: $buyer,
            amount: 200.00,
            context: 'Refund for losing betslip #BS-TEST',
        );

        $buyerWallet->refresh();

        $this->assertSame(200.00, (float) $buyerWallet->balance);
        $this->assertSame(0.00, (float) $buyerWallet->escrow_balance);
    }
    public function test_refund_escrow_writes_per_leg_descriptions(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);

        app(WalletService::class)->refundEscrow(
            user: $buyer,
            amount: 200.00,
            context: 'Betslip #TEST-R',
        );

        $rows = Transaction::where('user_id', $buyer->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rows);

        // Escrow leg — internal, prefixed "Escrow released for".
        $this->assertSame('escrow', $rows[0]->balance_type);
        $this->assertStringContainsString('Escrow released for', $rows[0]->description);
        $this->assertStringContainsString('Betslip #TEST-R', $rows[0]->description);

        // Available leg — user-facing, prefixed "Refund for".
        $this->assertSame('available', $rows[1]->balance_type);
        $this->assertStringContainsString('Refund for', $rows[1]->description);
        $this->assertStringContainsString('Betslip #TEST-R', $rows[1]->description);
    }
    public function test_refund_escrow_writes_two_ledger_rows(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);

        app(WalletService::class)->refundEscrow(
            user: $buyer,
            amount: 200.00,
        );

        $rows = Transaction::where('user_id', $buyer->id)->orderBy('id')->get();

        $this->assertCount(2, $rows);

        // Escrow leg: debited.
        $this->assertSame('escrow', $rows[0]->balance_type);
        $this->assertSame(-200.00, (float) $rows[0]->amount);
        $this->assertSame(200.00, (float) $rows[0]->balance_before);
        $this->assertSame(0.00, (float) $rows[0]->balance_after);

        // Available leg: credited.
        $this->assertSame('available', $rows[1]->balance_type);
        $this->assertSame(200.00, (float) $rows[1]->amount);
        $this->assertSame(0.00, (float) $rows[1]->balance_before);
        $this->assertSame(200.00, (float) $rows[1]->balance_after);
    }

    public function test_refund_escrow_throws_when_escrow_is_insufficient_and_nothing_moves(): void
    {
        [$buyer, $buyerWallet] = $this->makeUserWithWallet(balance: 0, escrow: 50.00);

        try {
            app(WalletService::class)->refundEscrow(
                user: $buyer,
                amount: 200.00,
            );
            $this->fail('Expected InsufficientBalanceException.');
        } catch (\App\Exceptions\InsufficientBalanceException) {
            // expected
        }

        $buyerWallet->refresh();
        $this->assertSame(0.00, (float) $buyerWallet->balance);
        $this->assertSame(50.00, (float) $buyerWallet->escrow_balance);
        $this->assertSame(0, Transaction::where('user_id', $buyer->id)->count());
    }

    public function test_settle_escrow_to_seller_writes_per_leg_descriptions(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();

        app(WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: 200.00,
            fee: 20.00,
            context: 'Betslip #TEST-1',
        );

        $buyerRow = Transaction::where('user_id', $buyer->id)->first();
        $sellerRows = Transaction::where('user_id', $seller->id)->orderBy('id')->get();
        $platformRow = Transaction::where('user_id', $platform->id)->first();

        $this->assertStringContainsString('Escrow released', $buyerRow->description);
        $this->assertStringContainsString('Betslip #TEST-1', $buyerRow->description);

        $this->assertStringContainsString('Payout', $sellerRows[0]->description);
        $this->assertStringNotContainsString('fee', strtolower($sellerRows[0]->description));

        $this->assertStringContainsString('Platform fee', $sellerRows[1]->description);

        $this->assertStringContainsString('Platform fee', $platformRow->description);
        $this->assertStringContainsString('from', $platformRow->description);
    }
}