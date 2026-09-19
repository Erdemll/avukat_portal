<?php

namespace App;

enum CaseProceedingType: string
{
    case Lawsuit = 'lawsuit';
    case Enforcement = 'enforcement';
    case Mediation = 'mediation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lawsuit => 'Dava',
            self::Enforcement => 'İcra',
            self::Mediation => 'Arabuluculuk',
            self::Other => 'Diğer',
        };
    }
}
