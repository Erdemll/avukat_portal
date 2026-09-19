<?php

namespace Database\Factories;

use App\CaseAssignmentRequestStatus;
use App\CaseAssignmentRequestType;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseAssignmentRequest>
 */
class CaseAssignmentRequestFactory extends Factory
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
            'type' => CaseAssignmentRequestType::Transfer,
            'requested_by' => User::factory()->lawyer(),
            'reason' => fake()->sentence(),
            'status' => CaseAssignmentRequestStatus::Pending,
        ];
    }
}
