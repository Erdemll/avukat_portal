<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\CaseNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseNote>
 */
class CaseNoteFactory extends Factory
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
            'author_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_private' => true,
            'occurred_at' => now(),
        ];
    }
}
