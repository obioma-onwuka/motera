<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Actions\Auth\RegisterUserAction;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RegisterUserAction $registerAction): void
    {
        // 1. Create Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@motera.com'],
            [
                'name' => 'MOTERA Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        // 2. Create Sample Customers using the RegisterUserAction to ensure they have bank accounts
        $customers = [
            [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'password' => 'password',
            ],
        ];

        foreach ($customers as $customerData) {
            if (!User::where('email', $customerData['email'])->exists()) {
                $registerAction->execute($customerData);
            }
        }
    }
}
