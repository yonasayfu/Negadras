<?php

namespace App;

enum SessionAppearanceStatus: string
{
    case Queued = 'queued';
    case Live = 'live';
    case Scored = 'scored';
    case Completed = 'completed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Live => 'Live now',
            self::Scored => 'Scored',
            self::Completed => 'Completed',
            self::Skipped => 'Skipped',
        };
    }
}
