<?php

namespace Database\Factories;

use App\MediationStatus;
use App\Models\CaseFile;
use App\Models\Mediation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mediation>
 */
class MediationFactory extends Factory
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
            'mediation_file_no' => fake()->numerify('2026/####'),
            'mediator_name' => fake()->name(),
            'application_date' => today(),
            'meeting_date' => now()->addWeek(),
            'status' => MediationStatus::Ongoing,
            'created_by' => User::factory(),
        ];
    }
}
