<?php

namespace App;

enum PartyType: string
{
    case Individual = 'individual';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Gerçek Kişi',
            self::Company => 'Tüzel Kişi',
        };
    }
}
