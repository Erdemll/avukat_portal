<?php

namespace App;

enum CaseFilePartyRole: string
{
    case Client = 'client';
    case Plaintiff = 'plaintiff';
    case Defendant = 'defendant';
    case Creditor = 'creditor';
    case Debtor = 'debtor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Müvekkil',
            self::Plaintiff => 'Davacı',
            self::Defendant => 'Davalı',
            self::Creditor => 'Alacaklı',
            self::Debtor => 'Borçlu',
            self::Other => 'Diğer',
        };
    }
}
