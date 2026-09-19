<?php

namespace App\Models;

use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    protected $fillable = ['change_note'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Belge sürümleri değiştirilemez.'));
        static::deleting(fn () => throw new LogicException('Belge sürümleri silinemez.'));
    }

    protected function casts(): array
    {
        return ['version_no' => 'integer', 'size' => 'integer'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
