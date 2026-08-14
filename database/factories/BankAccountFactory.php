<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\KycTier;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
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
            'account_number' => fake()->unique()->numerify('##########'),
            'ledger_balance' => 0.00,
            'available_balance' => 0.00,
            'currency' => 'USD',
            'status' => AccountStatus::ACTIVE,
            'tier' => KycTier::TIER_1,
            'is_restricted' => false,
            'restriction_reason' => null,
        ];
    }

    /**
     * Indicate the account is restricted.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_restricted' => true,
            'restriction_reason' => 'Compliance review',
        ]);
    }

    /**
     * Set both balances to the given amount.
     */
    public function withBalance(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'ledger_balance' => $amount,
            'available_balance' => $amount,
        ]);
    }
}
