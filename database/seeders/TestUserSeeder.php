<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // [name, email, code, is_seller, balance]
        $users = [
            // Sellers
            ['Wazo Tank',     'wazo@betslip-pirates.test',    'WAZO-TANK-001', true,  1200],
            ['Phoenix Picks', 'phoenix@betslip-pirates.test', 'PHOE-PICK-002', true,  800],
            ['Data Driven',   'data@betslip-pirates.test',    'DATA-DRVN-003', true,  500],
            ['Sharp Shooter', 'sharp@betslip-pirates.test',   'SHAR-SHOT-004', true,  300],
            ['The Professor', 'prof@betslip-pirates.test',    'PROF-ESSR-005', true,  200],
            // Buyers
            ['Brian Kamau',   'brian@betslip-pirates.test',   'BRIA-KAMA-101', false, 2500],
            ['Aisha Mwangi',  'aisha@betslip-pirates.test',   'AISH-MWAN-102', false, 1800],
            ['John Otieno',   'john@betslip-pirates.test',    'JOHN-OTIE-103', false, 900],
            ['Grace Njeri',   'grace@betslip-pirates.test',   'GRAC-NJER-104', false, 600],
            ['Denis Wanjohi', 'denis@betslip-pirates.test',   'DENI-WANJ-105', false, 1500],
            ['Mike Kimani',   'mike@betslip-pirates.test',    'MIKE-KIMA-106', false, 400],
            ['Sarah Wanjiku', 'sarah@betslip-pirates.test',   'SARA-WANJ-107', false, 1000],
            ['Peter Njoroge', 'peter@betslip-pirates.test',   'PETE-NJOR-108', false, 750],
        ];

        foreach ($users as [$name, $email, $code, $isSeller, $balance]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => '2547' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'code' => $code,
                    'country_code' => '+254',
                    'bio' => $isSeller ? 'Professional sports analyst.' : null,
                    'profile_picture_url' => null,
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );

            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => $balance,
                    'escrow_balance' => 0,
                    'total_deposited' => $balance,
                    'total_withdrawn' => 0,
                    'currency' => 'KES',
                ]
            );
        }

        $this->command->info("✓ Seeded " . count($users) . " users with wallets");
        $this->command->warn("  Password for all test users: password");
    }
}