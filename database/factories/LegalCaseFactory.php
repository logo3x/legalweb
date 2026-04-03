<?php

namespace Database\Factories;

use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalCase>
 */
class LegalCaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_number' => 'LW-' . fake()->unique()->numerify('####-####'),
            'external_case_number' => fake()->optional()->numerify('####-#####-##-###-####-#####-##'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'case_type_id' => CaseType::factory(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['abierto', 'en_progreso', 'en_espera', 'cerrado', 'archivado']),
            'court' => fake()->optional()->company() . ' - Juzgado ' . fake()->numberBetween(1, 30),
            'judge' => fake()->optional()->name(),
            'opposing_party' => fake()->optional()->name(),
            'priority' => fake()->randomElement(['baja', 'media', 'alta', 'urgente']),
            'started_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cerrado',
            'closed_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
