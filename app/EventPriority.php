<?php

namespace App;

enum EventPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Düşük', self::Normal => 'Normal', self::High => 'Yüksek', self::Urgent => 'Acil'
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700',
            self::Normal => 'bg-blue-100 text-blue-700',
            self::High => 'bg-orange-100 text-orange-700',
            self::Urgent => 'bg-red-100 text-red-700',
        };
    }
}
