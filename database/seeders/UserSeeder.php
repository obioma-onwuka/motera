<?php

namespace Database\Seeders;

use App\Actions\Auth\RegisterUserAction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RegisterUserAction $registerAction): void
    {
        // 1. Create Super Admin
        $adminPassword = env('MOTERA_ADMIN_PASSWORD') ?: Str::password(24);

        $admin = User::firstOrCreate(
            ['email' => 'admin@motera.com'],
            [
                'name' => 'MOTERA Super Admin',
                'password' => Hash::make($adminPassword),
            ]
        );
        $admin->syncRoles(['Super Admin']);

        if (! env('MOTERA_ADMIN_PASSWORD') && $this->command) {
            $this->command->warn("Generated admin password: {$adminPassword}");
        }

        // 2. Create Sample Customers using the RegisterUserAction to ensure they have bank accounts
        if (! app()->environment('local')) {
            return;
        }

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
            if (! User::where('email', $customerData['email'])->exists()) {
                $registerAction->execute($customerData);
            }
        }
    }
}
