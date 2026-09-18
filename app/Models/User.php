<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
