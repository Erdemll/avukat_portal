<?php

namespace Database\Factories;

use App\HearingStatus;
use App\Models\CaseFile;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hearing>
 */
class HearingFactory extends Factory
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
            'title' => 'Ön inceleme duruşması',
            'court' => fake()->city().' 3. İş Mahkemesi',
            'hearing_at' => now()->addWeek(),
            'hearing_type' => 'Ön İnceleme',
            'description' => fake()->optional()->sentence(),
            'status' => HearingStatus::Scheduled,
            'created_by' => User::factory(),
        ];
    }
}
