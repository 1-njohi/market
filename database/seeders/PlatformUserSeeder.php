<?php
// database/seeders/PlatformUserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformUserSeeder extends Seeder
{
    public function run(): void
    {
        $platform = User::firstOrCreate(
            ['email' => 'platform@betslip-pirates.com'],
            [
                'name' => 'Betslip Pirates Platform',
                'phone' => '0000000000',
                'code' => 'PLATFORM',
                'country_code' => '+254',
                'password' => bcrypt(Str::random(64)), // unusable
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $platform->id],
            ['balance' => 0, 'escrow_balance' => 0, 'currency' => 'KES']
        );
    }
}