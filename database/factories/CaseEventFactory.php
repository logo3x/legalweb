<?php

namespace Database\Factories;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseEvent>
 */
class CaseEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'legal_case_id' => LegalCase::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'event_date' => fake()->dateTimeBetween('-1 year', '+3 months'),
            'event_type' => fake()->randomElement(['actuacion', 'audiencia', 'notificacion', 'memorial', 'auto', 'sentencia']),
            'is_milestone' => fake()->boolean(20),
            'user_id' => User::factory(),
        ];
    }

    public function milestone(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_milestone' => true,
        ]);
    }
}
