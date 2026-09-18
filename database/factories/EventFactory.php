<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type_id' => EventType::factory(),
            'created_by' => User::factory(),
            'assigned_lawyer_id' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }
}
