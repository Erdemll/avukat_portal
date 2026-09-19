<?php

namespace App;

enum ServiceNoticeType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Court = 'court';
    case Enforcement = 'enforcement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Incoming => 'Gelen Tebligat',
            self::Outgoing => 'Giden Tebligat',
            self::Court => 'Mahkeme Tebligatı',
            self::Enforcement => 'İcra Tebligatı',
            self::Other => 'Diğer',
        };
    }
}
