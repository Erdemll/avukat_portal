<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size',
        'document_type',
        'title',
        'archived_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            if (($document->event_id === null) === ($document->case_file_id === null)) {
                throw new LogicException('Belge yalnız bir hukuki talebe veya hukuki dosyaya bağlı olmalıdır.');
            }
        });
    }

    protected function casts(): array
    {
        return ['size' => 'integer', 'archived_at' => 'datetime'];
    }

    protected function humanSize(): Attribute
    {
        return Attribute::get(function (): string {
            $size = $this->size;
            $units = ['B', 'KB', 'MB', 'GB'];

            foreach ($units as $unit) {
                if ($size < 1024 || $unit === 'GB') {
                    return number_format($size, $unit === 'B' ? 0 : 1, ',', '.').' '.$unit;
                }

                $size /= 1024;
            }

            return '0 B';
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function eventUpdate(): BelongsTo
    {
        return $this->belongsTo(EventUpdate::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->ofMany('version_no', 'max');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->whereHas('caseFile', fn (Builder $caseFiles) => $caseFiles->visibleTo($user))
                ->orWhereHas('event', fn (Builder $events) => $events->visibleTo($user));
        });
    }
}
