<?php

namespace App;

enum CasePartySide: string
{
    case Own = 'own';
    case Opposing = 'opposing';
    case Neutral = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Bizim Taraf',
            self::Opposing => 'Karşı Taraf',
            self::Neutral => 'Tarafsız / Diğer',
        };
    }
}
