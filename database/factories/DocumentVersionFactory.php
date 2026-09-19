<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version_no' => 1,
            'original_name' => 'belge.pdf',
            'stored_name' => fake()->uuid().'.pdf',
            'disk' => 'legal_private',
            'path' => 'case-files/'.fake()->numberBetween(1, 999).'/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'sha256' => hash('sha256', fake()->uuid()),
            'uploaded_by' => User::factory(),
            'change_note' => null,
        ];
    }
}
