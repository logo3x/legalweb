<?php

namespace Database\Factories;

use App\Models\Firm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Firm>
 */
class FirmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'nit' => fake()->numerify('#########-#'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('601#######'),
            'city' => fake()->randomElement(['Bogotá', 'Medellín', 'Cali', 'Barranquilla']),
            'department' => 'Cundinamarca',
            'onboarding_completed' => true,
        ];
    }
}
