<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@tongiveaway.com',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
        ]);

        // Create wallet for admin user
        $admin = \App\Models\User::where('email', 'admin@tongiveaway.com')->first();
        if ($admin && !$admin->wallet) {
            \App\Models\Wallet::create([
                'user_id' => $admin->id,
                'balance' => 1000.0,
                'gems' => 10000,
                'diamonds' => 100,
                'ton_address' => 'EQAdmin_Wallet_Address_Example',
            ]);
        }
    }
}
