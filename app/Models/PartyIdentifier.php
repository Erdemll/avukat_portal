<?php

namespace App\Models;

use App\PartyIdentifierType;
use Database\Factories\PartyIdentifierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PartyIdentifier extends Model
{
    /** @use HasFactory<PartyIdentifierFactory> */
    use HasFactory;

    protected $fillable = ['type', 'value', 'country_code'];

    protected $hidden = ['value', 'value_hash'];

    protected static function booted(): void
    {
        static::saving(function (self $identifier): void {
            $countryCode = Str::upper($identifier->country_code ?: 'TR');
            if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
                throw new InvalidArgumentException('Ülke kodu iki harfli ISO biçiminde olmalıdır.');
            }
            $identifier->country_code = $countryCode;

            if ($identifier->isDirty('value')) {
                $identifier->value_hash = self::hashValue($identifier->value);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => PartyIdentifierType::class,
            'value' => 'encrypted',
        ];
    }

    public static function hashValue(string $value): string
    {
        $key = config('legal.identifier_hash_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Hassas kimlik arama anahtarı yapılandırılmamış.');
        }

        $normalized = mb_strtoupper(preg_replace('/[^\p{L}\p{N}]+/u', '', trim($value)) ?? '');

        return hash_hmac('sha256', $normalized, $key);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
