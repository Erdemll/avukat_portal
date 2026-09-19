<?php

namespace App\Models;

use App\CaseFilePartyRole;
use App\CasePartySide;
use Database\Factories\CaseFilePartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CaseFileParty extends Pivot
{
    /** @use HasFactory<CaseFilePartyFactory> */
    use HasFactory;

    public $incrementing = true;

    protected $table = 'case_file_parties';

    protected $hidden = ['active_marker'];

    protected static function booted(): void
    {
        static::saving(function (self $caseParty): void {
            $caseParty->forceFill(['active_marker' => $caseParty->left_at === null ? true : null]);
        });
    }

    protected $fillable = ['case_file_id', 'party_id', 'role', 'side', 'is_primary', 'added_by', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return [
            'role' => CaseFilePartyRole::class,
            'side' => CasePartySide::class,
            'is_primary' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
