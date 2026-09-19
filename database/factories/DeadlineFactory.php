<?php

namespace Database\Factories;

use App\DeadlineStatus;
use App\Models\CaseFile;
use App\Models\Deadline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deadline>
 */
class DeadlineFactory extends Factory
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
            'assigned_lawyer_id' => User::factory()->lawyer(),
            'title' => 'Cevap süresi',
            'description' => fake()->optional()->sentence(),
            'starts_at' => now(),
            'due_at' => now()->addDays(14),
            'status' => DeadlineStatus::Open,
            'created_by' => User::factory(),
        ];
    }
}
