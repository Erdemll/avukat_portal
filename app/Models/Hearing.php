<?php

namespace App\Models;

use App\HearingStatus;
use Database\Factories\HearingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hearing extends Model
{
    /** @use HasFactory<HearingFactory> */
    use HasFactory;

    protected $fillable = ['title', 'court', 'hearing_at', 'hearing_type', 'description', 'result', 'next_hearing_at', 'status'];

    protected $attributes = ['status' => 'scheduled'];

    protected function casts(): array
    {
        return ['hearing_at' => 'datetime', 'next_hearing_at' => 'datetime', 'reminder_sent_at' => 'datetime', 'status' => HearingStatus::class, 'lock_version' => 'integer'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('caseFile', fn (Builder $cases) => $cases->visibleTo($user));
    }
}
