<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'name', 'email', 'tc_kimlik_no', 'phone', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_enabled_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function assignedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'assigned_lawyer_id');
    }

    public function eventUpdates(): HasMany
    {
        return $this->hasMany(EventUpdate::class);
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function createdCaseFiles(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'created_by');
    }

    public function caseFileAssignments(): HasMany
    {
        return $this->hasMany(CaseFileAssignment::class, 'lawyer_id');
    }

    public function twoFactorChallenges(): HasMany
    {
        return $this->hasMany(TwoFactorChallenge::class);
    }

    public function hasTwoFactorAuthenticationEnabled(): bool
    {
        return $this->two_factor_enabled_at !== null;
    }

    public function activeCaseFiles(): BelongsToMany
    {
        return $this->belongsToMany(CaseFile::class, 'case_file_assignments', 'lawyer_id', 'case_file_id')
            ->using(CaseFileAssignment::class)
            ->wherePivotNull('ended_at')
            ->withPivot(['role', 'assigned_by', 'started_at', 'reason'])
            ->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->slug === $role;
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isLawyer(): bool
    {
        return $this->hasRole('lawyer');
    }

    public function isEmployee(): bool
    {
        return $this->hasRole('employee');
    }
}
