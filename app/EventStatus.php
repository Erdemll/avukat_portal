<?php

namespace App;

enum EventStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Açık', self::InProgress => 'Devam Ediyor', self::Waiting => 'Beklemede', self::Resolved => 'Çözüldü', self::Closed => 'Kapalı'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-blue-100 text-blue-800',
            self::InProgress => 'bg-amber-100 text-amber-800',
            self::Waiting => 'bg-orange-100 text-orange-800',
            self::Resolved => 'bg-emerald-100 text-emerald-800',
            self::Closed => 'bg-slate-100 text-slate-800',
        };
    }
}
