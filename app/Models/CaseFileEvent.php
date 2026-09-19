<?php

namespace App\Models;

use App\CaseEventRelationType;
use Database\Factories\CaseFileEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CaseFileEvent extends Pivot
{
    /** @use HasFactory<CaseFileEventFactory> */
    use HasFactory;

    public $incrementing = true;

    protected $table = 'case_file_event';

    protected $fillable = ['case_file_id', 'event_id', 'relation_type', 'linked_by', 'linked_at'];

    protected function casts(): array
    {
        return ['relation_type' => CaseEventRelationType::class, 'linked_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
