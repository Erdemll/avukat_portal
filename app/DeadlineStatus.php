<?php

namespace App;

enum DeadlineStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Açık',
            self::Completed => 'Tamamlandı',
            self::Cancelled => 'İptal',
        };
    }
}
