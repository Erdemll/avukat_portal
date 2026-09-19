<?php

namespace Database\Factories;

use App\EventPriority;
use App\LegalTaskStatus;
use App\Models\CaseFile;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalTask>
 */
class LegalTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_file_id' => CaseFile::factory(),
            'assigned_to' => User::factory()->lawyer(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => EventPriority::Normal,
            'due_at' => now()->addDays(3),
            'status' => LegalTaskStatus::Pending,
        ];
    }
}
