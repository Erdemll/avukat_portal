<?php

namespace App\Models;

use App\CaseFileStatus;
use App\EventPriority;
use Database\Factories\CaseFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class CaseFile extends Model
{
    /** @use HasFactory<CaseFileFactory> */
    use HasFactory;

    protected $fillable = ['case_type_id', 'title', 'status', 'priority', 'description', 'opened_at', 'closed_at'];

    protected $attributes = ['status' => 'active', 'priority' => 'normal'];

    protected static function booted(): void
    {
        static::updating(function (self $caseFile): void {
            if ($caseFile->isDirty('case_no')) {
                throw new LogicException('Hukuki dosya numarası değiştirilemez.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => CaseFileStatus::class,
            'priority' => EventPriority::class,
            'opened_at' => 'date',
            'closed_at' => 'date',
            'lock_version' => 'integer',
        ];
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->using(CaseFileEvent::class)
            ->withPivot(['relation_type', 'linked_by', 'linked_at'])
            ->withTimestamps();
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'case_file_parties')
            ->using(CaseFileParty::class)
            ->withPivot(['id', 'role', 'side', 'is_primary', 'added_by', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeParties(): BelongsToMany
    {
        return $this->parties()->wherePivotNull('left_at');
    }

    public function proceedings(): HasMany
    {
        return $this->hasMany(CaseProceeding::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CaseFileAssignment::class);
    }

    public function activeLawyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'case_file_assignments', 'case_file_id', 'lawyer_id')
            ->using(CaseFileAssignment::class)
            ->wherePivotNull('ended_at')
            ->withPivot(['role', 'assigned_by', 'started_at', 'reason'])
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CaseFileStatusHistory::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class);
    }

    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LegalTask::class);
    }

    public function serviceNotices(): HasMany
    {
        return $this->hasMany(ServiceNotice::class);
    }

    public function mediations(): HasMany
    {
        return $this->hasMany(Mediation::class);
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(CaseFinancialEntry::class);
    }

    public function clientCommunications(): HasMany
    {
        return $this->hasMany(ClientCommunication::class);
    }

    public function assignmentRequests(): HasMany
    {
        return $this->hasMany(CaseAssignmentRequest::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            $user->isLawyer() => $query->whereHas('assignments', fn (Builder $assignment) => $assignment
                ->where('lawyer_id', $user->id)
                ->whereNull('ended_at')),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
