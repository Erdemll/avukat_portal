<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
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

    public function eventUpdate(): BelongsTo
    {
        return $this->belongsTo(EventUpdate::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
