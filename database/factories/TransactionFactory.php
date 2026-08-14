<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'reference' => 'MTR-TST-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'type' => 'transfer',
            'amount' => 100.00,
            'status' => TransactionStatus::SUCCESSFUL,
            'description' => fake()->sentence(3),
            'metadata' => [],
        ];
    }
}
