<?php

namespace Database\Factories;

use App\CaseEventRelationType;
use App\Models\CaseFile;
use App\Models\CaseFileEvent;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFileEvent>
 */
class CaseFileEventFactory extends Factory
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
            'event_id' => Event::factory(),
            'relation_type' => CaseEventRelationType::Related,
            'linked_by' => User::factory(),
            'linked_at' => now(),
        ];
    }
}
