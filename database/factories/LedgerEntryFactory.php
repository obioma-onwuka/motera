<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'bank_account_id' => BankAccount::factory(),
            'reference' => 'MTR-TST-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'type' => 'debit',
            'amount' => 100.00,
            'balance_after' => 0.00,
            'description' => fake()->sentence(3),
        ];
    }

    /**
     * Indicate the entry is a contra (system) entry with no bank account.
     */
    public function contra(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_account_id' => null,
        ]);
    }
}
