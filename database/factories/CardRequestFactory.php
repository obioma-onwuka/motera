<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\CardRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardRequest>
 */
class CardRequestFactory extends Factory
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
            'type' => 'physical',
            'status' => 'pending',
            'card_name' => fake()->name(),
            'delivery_address' => fake()->address(),
            'fee' => 1000.00,
            'metadata' => [],
        ];
    }
}
