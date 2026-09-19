<?php

namespace Database\Seeders;

use App\CaseTypeCategory;
use App\Models\CaseType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CaseTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['Dava', CaseTypeCategory::Lawsuit],
            ['İcra', CaseTypeCategory::Enforcement],
            ['Arabuluculuk', CaseTypeCategory::Mediation],
            ['İhtarname', CaseTypeCategory::Other],
            ['Danışmanlık', CaseTypeCategory::Other],
            ['Sözleşme Uyuşmazlığı', CaseTypeCategory::Other],
            ['İş Hukuku', CaseTypeCategory::Lawsuit],
            ['Tüketici Uyuşmazlığı', CaseTypeCategory::Lawsuit],
            ['Ceza', CaseTypeCategory::Lawsuit],
            ['İdari', CaseTypeCategory::Lawsuit],
            ['Diğer', CaseTypeCategory::Other],
        ];

        foreach ($types as [$name, $category]) {
            CaseType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'category' => $category, 'is_active' => true],
            );
        }
    }
}
