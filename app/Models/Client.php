<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $fillable = ['status', 'client_since', 'notes'];

    protected function casts(): array
    {
        return ['client_since' => 'date', 'lock_version' => 'integer'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ClientCommunication::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            $user->isLawyer() => $query->where(function (Builder $query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('party.activeCaseFiles', fn (Builder $caseFiles) => $caseFiles->visibleTo($user));
            }),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
