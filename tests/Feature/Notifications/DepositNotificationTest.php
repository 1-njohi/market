<?php

namespace Tests\Feature\Notifications;

use App\Models\Deposit;
use App\Models\User;
use App\Services\PaystackDepositService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class DepositNotificationTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    public function test_successful_charge_credits_wallet_and_notifies_user(): void
    {
        [$user] = $this->makeUserWithWallet(balance: 0);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'reference' => 'DEP-TEST-' . uniqid(),
            'amount' => 1000.00,
            'currency' => 'KES',
            'status' => 'pending',
        ]);

        app(PaystackDepositService::class)->handleWebhook([
            'event' => 'charge.success',
            'data' => [
                'reference' => $deposit->reference,
                'amount' => 100000,   // kobo; 1000 KES
                'channel' => 'card',
                'metadata' => ['user_id' => $user->id],
            ],
        ]);

        // 1. Deposit transitioned to completed.
        $this->assertSame('completed', $deposit->fresh()->status);

        // 2. Wallet credited.
        $this->assertSame(1000.00, (float) $user->fresh()->wallet->balance);

        // 3. User got a notification of type 'deposit'.
        $row = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($row, 'User should have received a deposit notification.');

        $data = json_decode($row->data, true);
        $this->assertSame('deposit', $data['type']);
        $this->assertStringContainsString('1,000.00', $data['body']);
        $this->assertSame($deposit->reference, $data['reference'] ?? null);
    }
}