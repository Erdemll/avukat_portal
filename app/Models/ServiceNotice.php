<?php

namespace App\Models;

use App\ServiceNoticeType;
use Database\Factories\ServiceNoticeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceNotice extends Model
{
    /** @use HasFactory<ServiceNoticeFactory> */
    use HasFactory;

    protected $fillable = ['type', 'sender', 'recipient', 'notification_date', 'service_date', 'description'];

    protected function casts(): array
    {
        return ['type' => ServiceNoticeType::class, 'notification_date' => 'date', 'service_date' => 'date', 'lock_version' => 'integer'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
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
