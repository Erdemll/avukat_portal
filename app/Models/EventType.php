<?php

namespace App\Models;

use Database\Factories\EventTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    /** @use HasFactory<EventTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
