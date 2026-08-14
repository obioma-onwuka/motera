<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Biller;
use App\Models\BillPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillPayment>
 */
class BillPaymentFactory extends Factory
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
            'biller_id' => Biller::factory(),
            'amount' => 100.00,
            'reference' => 'MTR-BILL-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'customer_identifier' => fake()->numerify('##########'),
            'status' => 'successful',
            'metadata' => [],
        ];
    }
}
