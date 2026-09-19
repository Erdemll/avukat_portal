<?php

namespace Database\Factories;

use App\CaseFileStatus;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFile>
 */
class CaseFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_no' => 'TPN-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'case_type_id' => CaseType::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'status' => CaseFileStatus::Active,
            'priority' => 'normal',
            'description' => fake()->optional()->paragraph(),
            'opened_at' => today(),
            'closed_at' => null,
        ];
    }
}
