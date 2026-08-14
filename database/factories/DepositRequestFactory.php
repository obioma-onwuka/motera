<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\BankAccount;
use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositRequest>
 */
class DepositRequestFactory extends Factory
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
            'reference' => 'MTR-DEP-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'status' => RequestStatus::PENDING,
            'proof_path' => 'deposits/proofs/'.fake()->uuid().'.jpg',
            'admin_note' => null,
        ];
    }

    /**
     * Indicate the request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::PENDING,
        ]);
    }
}
