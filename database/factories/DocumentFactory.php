<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $fileType = fake()->randomElement(['pdf', 'docx', 'jpg', 'png', 'xlsx']);

        return [
            'legal_case_id' => LegalCase::factory(),
            'case_event_id' => null,
            'name' => fake()->words(3, true) . '.' . $fileType,
            'description' => fake()->optional()->sentence(),
            'file_path' => 'documents/' . fake()->uuid() . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(10240, 10485760),
            'uploaded_by' => User::factory(),
        ];
    }
}
