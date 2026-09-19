<?php

namespace Database\Factories;

use App\Models\CaseNumberSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseNumberSequence>
 */
class CaseNumberSequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefix' => 'TPN',
            'year' => now()->year,
            'next_number' => 1,
        ];
    }
}
