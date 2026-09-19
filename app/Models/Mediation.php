<?php

namespace App\Models;

use App\MediationStatus;
use Database\Factories\MediationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mediation extends Model
{
    /** @use HasFactory<MediationFactory> */
    use HasFactory;

    protected $fillable = ['mediation_file_no', 'mediator_name', 'application_date', 'meeting_date', 'completion_date', 'status', 'result'];

    protected $attributes = ['status' => 'ongoing'];

    protected function casts(): array
    {
        return ['application_date' => 'date', 'meeting_date' => 'datetime', 'completion_date' => 'date', 'status' => MediationStatus::class, 'lock_version' => 'integer'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
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
