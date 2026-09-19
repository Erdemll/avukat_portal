<?php

namespace Database\Factories;

use App\CaseFileStatus;
use App\Models\CaseFile;
use App\Models\CaseFileStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFileStatusHistory>
 */
class CaseFileStatusHistoryFactory extends Factory
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
            'from_status' => null,
            'to_status' => CaseFileStatus::Active,
            'reason' => fake()->optional()->sentence(),
            'changed_by' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
