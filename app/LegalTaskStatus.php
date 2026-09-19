<?php

namespace App;

enum LegalTaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::InProgress => 'Devam Ediyor',
            self::Completed => 'Tamamlandı',
            self::Cancelled => 'İptal',
        };
    }
}
