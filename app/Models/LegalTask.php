<?php

namespace App\Models;

use App\EventPriority;
use App\LegalTaskStatus;
use Database\Factories\LegalTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalTask extends Model
{
    /** @use HasFactory<LegalTaskFactory> */
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = ['title', 'description', 'priority', 'due_at', 'status', 'completed_at'];

    protected $attributes = ['status' => 'pending', 'priority' => 'normal'];

    protected function casts(): array
    {
        return ['priority' => EventPriority::class, 'due_at' => 'datetime', 'status' => LegalTaskStatus::class, 'completed_at' => 'datetime', 'lock_version' => 'integer'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            default => $query->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $personal) use ($user): void {
                    $personal->whereNull('case_file_id')
                        ->where('assigned_to', $user->id);
                })->orWhere(function (Builder $caseTask) use ($user): void {
                    $caseTask->whereNotNull('case_file_id')
                        ->whereHas('caseFile', fn (Builder $cases) => $cases->visibleTo($user));
                });
            }),
        };
    }
}
