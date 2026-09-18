<?php

namespace Database\Factories;

use App\Models\EventUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventUpdate>
 */
class EventUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'savcilik' => fake()->optional(0.5)->randomElement([
                'İstanbul Anadolu 3. Asliye Ceza Savcılığı',
                'Ankara Cumhuriyet Savcılığı',
                'İstanbul 2. Sulh Ceza Savcılığı',
                null,
            ]),
            'description' => fake()->paragraph(),
        ];
    }
}
