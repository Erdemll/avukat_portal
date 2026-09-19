<?php

namespace App\Models;

use Database\Factories\CaseNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNote extends Model
{
    /** @use HasFactory<CaseNoteFactory> */
    use HasFactory;

    protected $fillable = ['body', 'is_private', 'occurred_at'];

    protected function casts(): array
    {
        return ['is_private' => 'boolean', 'occurred_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
