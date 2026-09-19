<?php

namespace App;

enum CaseAssignmentRole: string
{
    case Lead = 'lead';
    case Lawyer = 'lawyer';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lider Avukat',
            self::Lawyer => 'Avukat',
        };
    }
}
