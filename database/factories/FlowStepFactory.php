<?php

namespace Database\Factories;

use App\Models\CaseFlow;
use App\Models\FlowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlowStep>
 */
class FlowStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_flow_id' => CaseFlow::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'order' => fake()->numberBetween(1, 20),
            'days_limit' => fake()->optional()->numberBetween(1, 90),
            'is_required' => fake()->boolean(80),
        ];
    }
}
