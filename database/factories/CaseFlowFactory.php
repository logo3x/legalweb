<?php

namespace Database\Factories;

use App\Models\CaseFlow;
use App\Models\CaseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFlow>
 */
class CaseFlowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_type_id' => CaseType::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
