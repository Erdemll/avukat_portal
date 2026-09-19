<?php

namespace App;

enum CaseFileStatus: string
{
    case Active = 'active';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Waiting => 'Beklemede',
            self::Resolved => 'Sonuçlandı',
            self::Closed => 'Kapalı',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-800',
            self::Waiting => 'bg-amber-100 text-amber-800',
            self::Resolved => 'bg-blue-100 text-blue-800',
            self::Closed => 'bg-slate-200 text-slate-700',
        };
    }

    /** @return array<int, self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Active => [self::Waiting, self::Resolved],
            self::Waiting => [self::Active, self::Resolved],
            self::Resolved => [self::Active, self::Closed],
            self::Closed => [self::Active],
        };
    }
}
