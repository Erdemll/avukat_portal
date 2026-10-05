<?php

namespace App;

enum CaseFilePartyRole: string
{
    case Client = 'client';
    case Plaintiff = 'plaintiff';
    case Defendant = 'defendant';
    case Creditor = 'creditor';
    case Debtor = 'debtor';
    case Complainant = 'complainant';
    case Suspect = 'suspect';
    case Accused = 'accused';
    case Victim = 'victim';
    case Witness = 'witness';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Müvekkil',
            self::Plaintiff => 'Davacı',
            self::Defendant => 'Davalı',
            self::Creditor => 'Alacaklı',
            self::Debtor => 'Borçlu',
            self::Complainant => 'Müşteki',
            self::Suspect => 'Şüpheli',
            self::Accused => 'Sanık',
            self::Victim => 'Mağdur',
            self::Witness => 'Tanık',
            self::Other => 'Diğer',
        };
    }
}
