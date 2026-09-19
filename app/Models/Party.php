<?php

namespace App\Models;

use App\PartyType;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['type', 'name', 'surname', 'company_name', 'phone', 'email', 'address', 'notes'];

    protected function casts(): array
    {
        return ['type' => PartyType::class];
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->type === PartyType::Company
            ? (string) $this->company_name
            : trim($this->name.' '.$this->surname));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PartyIdentifier::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function caseFiles(): BelongsToMany
    {
        return $this->belongsToMany(CaseFile::class, 'case_file_parties')
            ->using(CaseFileParty::class)
            ->withPivot(['id', 'role', 'side', 'is_primary', 'added_by', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeCaseFiles(): BelongsToMany
    {
        return $this->caseFiles()->wherePivotNull('left_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isManager() => $query,
            $user->isLawyer() => $query->where(function (Builder $query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('activeCaseFiles', fn (Builder $caseFiles) => $caseFiles->visibleTo($user));
            }),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
