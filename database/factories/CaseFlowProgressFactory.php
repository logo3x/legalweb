<?php

namespace Database\Factories;

use App\Models\CaseFlowProgress;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFlowProgress>
 */
class CaseFlowProgressFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['pendiente', 'en_progreso', 'completado', 'omitido']);

        return [
            'legal_case_id' => LegalCase::factory(),
            'flow_step_id' => FlowStep::factory(),
            'status' => $status,
            'completed_at' => $status === 'completado' ? fake()->dateTimeBetween('-6 months', 'now') : null,
            'completed_by' => $status === 'completado' ? User::factory() : null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
