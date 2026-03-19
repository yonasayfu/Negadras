<?php

namespace App;

enum CompetitionSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Live => 'Live',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Scheduled => 'review',
            self::Live => 'published',
            self::Paused => 'review',
            self::Completed => 'draft',
            self::Archived => 'archived',
        };
    }
}
