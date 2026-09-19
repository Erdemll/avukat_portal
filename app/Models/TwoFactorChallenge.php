<?php

namespace App\Models;

use Database\Factories\TwoFactorChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorChallenge extends Model
{
    /** @use HasFactory<TwoFactorChallengeFactory> */
    use HasFactory;

    protected $fillable = ['code_hash', 'attempts', 'expires_at', 'consumed_at'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
