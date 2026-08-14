<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycSubmission>
 */
class KycSubmissionFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date(),
            'address' => fake()->address(),
            'id_type' => 'passport',
            'id_number' => fake()->unique()->bothify('P#########'),
            'status' => KycStatus::PENDING,
            'admin_note' => null,
            'processed_at' => null,
        ];
    }
}
