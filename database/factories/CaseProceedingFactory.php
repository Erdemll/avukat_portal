<?php

namespace Database\Factories;

use App\CaseProceedingType;
use App\Models\CaseFile;
use App\Models\CaseProceeding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseProceeding>
 */
class CaseProceedingFactory extends Factory
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
            'type' => CaseProceedingType::Lawsuit,
            'courthouse' => fake()->city().' Adliyesi',
            'authority_name' => fake()->randomElement(['1. İş Mahkemesi', '3. Asliye Hukuk Mahkemesi']),
            'principal_year' => now()->year,
            'principal_number' => (string) fake()->numberBetween(1, 9999),
            'status' => 'active',
            'opened_at' => today(),
        ];
    }
}
