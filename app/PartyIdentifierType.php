<?php

namespace App;

enum PartyIdentifierType: string
{
    case Tckn = 'tckn';
    case TaxNumber = 'tax_number';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tckn => 'T.C. Kimlik No',
            self::TaxNumber => 'Vergi No',
            self::Other => 'Diğer',
        };
    }
}
