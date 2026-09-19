<?php

namespace App\Models;

use App\CaseFileStatus;
use Database\Factories\CaseFileStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseFileStatusHistory extends Model
{
    /** @use HasFactory<CaseFileStatusHistoryFactory> */
    use HasFactory;

    protected $fillable = ['from_status', 'to_status', 'reason', 'changed_at'];

    protected function casts(): array
    {
        return ['from_status' => CaseFileStatus::class, 'to_status' => CaseFileStatus::class, 'changed_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
