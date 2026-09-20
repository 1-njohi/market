<?php

namespace Tests\Feature\Model;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_credit_and_is_debit_classify_by_sign(): void
    {
        $user = User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        $credit = $this->makeTransaction($user, '200.00');
        $debit  = $this->makeTransaction($user, '-200.00');
        $zero   = $this->makeTransaction($user, '0.00');

        $this->assertTrue($credit->isCredit());
        $this->assertFalse($credit->isDebit());

        $this->assertFalse($debit->isCredit());
        $this->assertTrue($debit->isDebit());

        $this->assertFalse($zero->isCredit(), 'Zero is not a credit.');
        $this->assertFalse($zero->isDebit(),  'Zero is not a debit.');
    }

    public function test_absolute_amount_is_always_positive_string(): void
    {
        $user = User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        $debit = $this->makeTransaction($user, '-200.00');

        $this->assertSame('200.00', $debit->absolute_amount);
    }

    private function makeTransaction(User $user, string $amount): Transaction
    {
        return Transaction::create([
            'user_id'        => $user->id,
            'type'           => 'purchase',
            'amount'         => $amount,
            'balance_type'   => 'available',
            'balance_before' => '0.00',
            'balance_after'  => '0.00',
            'status'         => 'completed',
            'reference'      => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}