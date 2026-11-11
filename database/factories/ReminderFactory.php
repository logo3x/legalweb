<?php

namespace Database\Factories;

use App\Models\Firm;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = fake()->dateTimeBetween('now', '+30 days');

        return [
            'firm_id' => Firm::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['audiencia', 'vencimiento', 'reunion', 'tarea']),
            'due_date' => $dueDate,
            'remind_at' => (clone $dueDate)->modify('-1 day'),
            'is_completed' => false,
            'priority' => fake()->randomElement(['baja', 'media', 'alta', 'urgente']),
        ];
    }
}
