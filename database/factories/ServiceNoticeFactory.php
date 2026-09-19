<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\ServiceNotice;
use App\Models\User;
use App\ServiceNoticeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceNotice>
 */
class ServiceNoticeFactory extends Factory
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
            'type' => ServiceNoticeType::Court,
            'sender' => 'Mahkeme Kalemi',
            'recipient' => 'Tepenet',
            'notification_date' => today(),
            'service_date' => today(),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
