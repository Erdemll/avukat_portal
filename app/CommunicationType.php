<?php

namespace App;

enum CommunicationType: string
{
    case Phone = 'phone';
    case Email = 'email';
    case Meeting = 'meeting';
    case Message = 'message';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Telefon',
            self::Email => 'E-posta',
            self::Meeting => 'Toplantı',
            self::Message => 'Mesaj',
        };
    }
}
