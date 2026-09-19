<?php

namespace App\Models;

use Database\Factories\CaseNumberSequenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseNumberSequence extends Model
{
    /** @use HasFactory<CaseNumberSequenceFactory> */
    use HasFactory;

    protected $fillable = ['prefix', 'year', 'next_number'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'next_number' => 'integer'];
    }
}
