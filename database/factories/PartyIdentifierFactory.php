<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\PartyIdentifier;
use App\Models\User;
use App\PartyIdentifierType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartyIdentifier>
 */
class PartyIdentifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'type' => PartyIdentifierType::Tckn,
            'value' => fake()->unique()->numerify('###########'),
            'country_code' => 'TR',
            'created_by' => User::factory(),
        ];
    }
}
