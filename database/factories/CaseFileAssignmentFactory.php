<?php

namespace Database\Factories;

use App\CaseAssignmentRole;
use App\Models\CaseFile;
use App\Models\CaseFileAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFileAssignment>
 */
class CaseFileAssignmentFactory extends Factory
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
            'lawyer_id' => User::factory()->lawyer(),
            'role' => CaseAssignmentRole::Lawyer,
            'assigned_by' => User::factory(),
            'started_at' => now(),
            'ended_at' => null,
            'ended_by' => null,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
