<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalRequest>
 */
class WithdrawalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_account_id' => BankAccount::factory(),
            'amount' => 500.00,
            'reference' => 'MTR-WTH-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'status' => RequestStatus::PENDING,
            'bank_name' => 'GTBank',
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'admin_note' => null,
        ];
    }
}
