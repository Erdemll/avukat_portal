<?php

namespace Database\Factories;

use App\CaseFilePartyRole;
use App\CasePartySide;
use App\Models\CaseFile;
use App\Models\CaseFileParty;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseFileParty>
 */
class CaseFilePartyFactory extends Factory
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
            'party_id' => Party::factory(),
            'role' => CaseFilePartyRole::Client,
            'side' => CasePartySide::Own,
            'is_primary' => true,
            'added_by' => User::factory(),
            'joined_at' => now(),
        ];
    }
}
