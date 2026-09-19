<?php

namespace App\Models;

use App\CaseAssignmentRequestStatus;
use App\CaseAssignmentRequestType;
use Database\Factories\CaseAssignmentRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignmentRequest extends Model
{
    /** @use HasFactory<CaseAssignmentRequestFactory> */
    use HasFactory;

    protected $fillable = ['type', 'reason', 'status', 'decision_note'];

    protected $attributes = ['status' => 'pending'];

    protected static function booted(): void
    {
        static::saving(function (self $request): void {
            $request->active_marker = $request->status === CaseAssignmentRequestStatus::Pending ? true : null;
        });
    }

    protected function casts(): array
    {
        return ['type' => CaseAssignmentRequestType::class, 'status' => CaseAssignmentRequestStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requestedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isManager() ? $query : $query->where(fn (Builder $scope) => $scope->where('requested_by', $user->id)->orWhere('requested_to', $user->id));
    }
}
