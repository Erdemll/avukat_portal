<?php

namespace App\Models;

use App\CaseTypeCategory;
use Database\Factories\CaseTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseType extends Model
{
    /** @use HasFactory<CaseTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'category', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'category' => CaseTypeCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function caseFiles(): HasMany
    {
        return $this->hasMany(CaseFile::class);
    }
}
