<?php

namespace App;

enum HearingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Planlandı',
            self::Completed => 'Tamamlandı',
            self::Postponed => 'Ertelendi',
            self::Cancelled => 'İptal',
        };
    }
}
