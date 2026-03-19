<?php

namespace App;

enum DashboardProjectionStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Active = 'active';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Ended => 'Ended',
        };
    }
}
