<?php

namespace Database\Factories;

use App\CommunicationType;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientCommunication>
 */
class ClientCommunicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory()->lawyer(),
            'type' => CommunicationType::Phone,
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'communication_at' => now(),
        ];
    }
}
