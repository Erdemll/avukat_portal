<?php

namespace App\Models;

use App\CaseProceedingType;
use Database\Factories\CaseProceedingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseProceeding extends Model
{
    /** @use HasFactory<CaseProceedingFactory> */
    use HasFactory;

    protected $fillable = ['type', 'courthouse', 'authority_name', 'court_type', 'principal_year', 'principal_number', 'decision_year', 'decision_number', 'external_file_number', 'status', 'opened_at', 'closed_at'];

    protected function casts(): array
    {
        return [
            'type' => CaseProceedingType::class,
            'principal_year' => 'integer',
            'decision_year' => 'integer',
            'opened_at' => 'date',
            'closed_at' => 'date',
        ];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }
}
