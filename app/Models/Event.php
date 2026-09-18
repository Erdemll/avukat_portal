<?php

namespace App\Models;

use App\EventPriority;
use App\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_type_id',
        'assigned_lawyer_id',
        'created_by',
        'title',
        'description',
        'current_process',
        'priority',
        'occurred_at',
    ];

    protected $attributes = [
        'system_status' => EventStatus::Open->value,
        'priority' => EventPriority::Normal->value,
    ];

    protected static function booted(): void
    {
        static::created(function (self $event): void {
            $event->forceFill([
                'event_no' => sprintf('OLY-%s-%06d', $event->created_at->format('Y'), $event->id),
            ])->saveQuietly();
        });

        static::deleting(function (self $event): void {
            $event->documents()->each(fn ($doc) => $doc->delete());
            $event->updates()->each(fn ($update) => $update->documents()->each(fn ($doc) => $doc->delete()));
        });
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'system_status' => EventStatus::class,
            'priority' => EventPriority::class,
        ];
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(EventUpdate::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            $user->isEmployee() => $query->where('created_by', $user->id),
            $user->isLawyer() => $query->where('assigned_lawyer_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
