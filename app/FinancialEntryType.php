<?php

namespace App;

enum FinancialEntryType: string
{
    case Receivable = 'receivable';
    case Payment = 'payment';
    case Expense = 'expense';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Receivable => 'Alacak',
            self::Payment => 'Tahsilat / Ödeme',
            self::Expense => 'Masraf',
            self::Reversal => 'Ters Kayıt',
        };
    }
}
