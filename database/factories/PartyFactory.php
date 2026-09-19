<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\User;
use App\PartyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PartyType::Individual,
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'company_name' => null,
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function company(): static
    {
        return $this->state(fn (): array => [
            'type' => PartyType::Company,
            'name' => null,
            'surname' => null,
            'company_name' => fake()->company(),
        ]);
    }
}
