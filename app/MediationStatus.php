<?php

namespace App;

enum MediationStatus: string
{
    case Ongoing = 'ongoing';
    case Agreement = 'agreement';
    case NoAgreement = 'no_agreement';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Devam Ediyor',
            self::Agreement => 'Anlaşma',
            self::NoAgreement => 'Anlaşamama',
        };
    }
}
