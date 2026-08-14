<?php

namespace Database\Factories;

use App\Enums\BillCategory;
use App\Models\Biller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Biller>
 */
class BillerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'category' => BillCategory::UTILITIES,
            'logo_url' => null,
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
