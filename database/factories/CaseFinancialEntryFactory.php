<?php

namespace Database\Factories;

use App\FinancialEntryType;
use App\Models\CaseFile;
use App\Models\CaseFinancialEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFinancialEntry>
 */
class CaseFinancialEntryFactory extends Factory
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
            'type' => FinancialEntryType::Receivable,
            'amount' => fake()->randomFloat(2, 100, 100000),
            'currency' => 'TRY',
            'description' => fake()->sentence(),
            'transaction_date' => today(),
            'created_by' => User::factory(),
        ];
    }
}
