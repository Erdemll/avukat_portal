<?php

namespace Database\Factories;

use App\Models\CaseFile;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentFolder>
 */
class DocumentFolderFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'created_by' => User::factory(),
        ];
    }
}
