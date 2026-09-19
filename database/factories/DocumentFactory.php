<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => 'belge.pdf',
            'stored_name' => fake()->uuid().'.pdf',
            'disk' => 'legal_private',
            'path' => 'events/'.fake()->numberBetween(1, 999).'/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
        ];
    }
}
