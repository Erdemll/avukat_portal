<?php

return [
    'case_number_prefix' => env('LEGAL_CASE_NUMBER_PREFIX', 'TPN'),
    'identifier_hash_key' => env('LEGAL_IDENTIFIER_HASH_KEY', env('APP_KEY')),
    'currencies' => ['TRY', 'USD', 'EUR', 'GBP'],
    'reminders' => [
        'hearing_hours' => (int) env('LEGAL_HEARING_REMINDER_HOURS', 48),
        'deadline_hours' => (int) env('LEGAL_DEADLINE_REMINDER_HOURS', 72),
    ],
];
