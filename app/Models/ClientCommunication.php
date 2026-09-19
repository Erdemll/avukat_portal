<?php

namespace App\Models;

use App\CommunicationType;
use Database\Factories\ClientCommunicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCommunication extends Model
{
    /** @use HasFactory<ClientCommunicationFactory> */
    use HasFactory;

    protected $fillable = ['type', 'subject', 'description', 'communication_at'];

    protected function casts(): array
    {
        return ['type' => CommunicationType::class, 'communication_at' => 'datetime', 'lock_version' => 'integer'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            default => $query->where(fn (Builder $entries) => $entries
                ->whereHas('client', fn (Builder $clients) => $clients->visibleTo($user))
                ->where(fn (Builder $scope) => $scope->whereNull('case_file_id')->where('user_id', $user->id)->orWhereHas('caseFile', fn (Builder $cases) => $cases->visibleTo($user)))),
        };
    }
}
