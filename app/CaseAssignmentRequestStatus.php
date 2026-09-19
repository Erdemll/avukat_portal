<?php

namespace App;

enum CaseAssignmentRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
            self::Cancelled => 'İptal Edildi',
        };
    }
}
