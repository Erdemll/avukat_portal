<?php

namespace App\Models;

use App\FinancialEntryType;
use Database\Factories\CaseFinancialEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class CaseFinancialEntry extends Model
{
    /** @use HasFactory<CaseFinancialEntryFactory> */
    use HasFactory;

    protected $fillable = ['type', 'amount', 'currency', 'description', 'transaction_date'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Finans hareketleri değiştirilemez.'));
        static::deleting(fn () => throw new LogicException('Finans hareketleri silinemez.'));
    }

    protected function casts(): array
    {
        return ['type' => FinancialEntryType::class, 'amount' => 'decimal:2', 'transaction_date' => 'date'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('caseFile', fn (Builder $cases) => $cases->visibleTo($user));
    }
}
