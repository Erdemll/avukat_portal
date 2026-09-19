<?php

namespace App\Models;

use App\DeadlineStatus;
use Database\Factories\DeadlineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deadline extends Model
{
    /** @use HasFactory<DeadlineFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'starts_at', 'due_at', 'status', 'completed_at'];

    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'due_at' => 'datetime', 'completed_at' => 'datetime', 'reminder_sent_at' => 'datetime', 'status' => DeadlineStatus::class, 'lock_version' => 'integer'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function assignedLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
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
