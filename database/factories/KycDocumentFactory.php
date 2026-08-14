<?php

namespace Database\Factories;

use App\Models\KycDocument;
use App\Models\KycSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycDocument>
 */
class KycDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kyc_submission_id' => KycSubmission::factory(),
            'document_type' => 'identity',
            'file_path' => 'kyc/'.fake()->uuid().'/'.fake()->uuid().'.jpg',
        ];
    }
}
