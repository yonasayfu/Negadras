<?php

namespace App;

enum ReviewerAssignmentStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Assigned => 'review',
            self::InProgress => 'review',
            self::Submitted => 'published',
            self::Expired => 'archived',
            self::Cancelled => 'archived',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Assigned, self::InProgress => true,
            self::Submitted, self::Expired, self::Cancelled => false,
        };
    }
}
