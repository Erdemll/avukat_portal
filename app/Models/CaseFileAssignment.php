<?php

namespace App\Models;

use App\CaseAssignmentRole;
use Database\Factories\CaseFileAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CaseFileAssignment extends Pivot
{
    /** @use HasFactory<CaseFileAssignmentFactory> */
    use HasFactory;

    public $incrementing = true;

    protected $table = 'case_file_assignments';

    protected $fillable = ['case_file_id', 'lawyer_id', 'role', 'assigned_by', 'started_at', 'ended_at', 'ended_by', 'reason'];

    protected $hidden = ['active_marker', 'active_lead_marker'];

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $isActive = $assignment->ended_at === null;
            $assignment->forceFill([
                'active_marker' => $isActive ? true : null,
                'active_lead_marker' => $isActive && $assignment->role === CaseAssignmentRole::Lead ? true : null,
            ]);
        });
    }

    protected function casts(): array
    {
        return ['role' => CaseAssignmentRole::class, 'started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}
