<?php

namespace Database\Factories;

use App\CaseTypeCategory;
use App\Models\CaseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseType>
 */
class CaseTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'category' => fake()->randomElement(CaseTypeCategory::cases()),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
