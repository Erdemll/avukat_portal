<?php

namespace App;

enum CaseAssignmentRequestType: string
{
    case Transfer = 'transfer';
    case Claim = 'claim';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Dosya Devir Talebi',
            self::Claim => 'Dosya Atama Talebi',
        };
    }
}
