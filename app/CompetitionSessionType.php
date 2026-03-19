<?php

namespace App;

enum CompetitionSessionType: string
{
    case Screening = 'screening';
    case Technical = 'technical';
    case Pitch = 'pitch';
    case Final = 'final';
    case Showcase = 'showcase';

    public function label(): string
    {
        return match ($this) {
            self::Screening => 'Screening',
            self::Technical => 'Technical',
            self::Pitch => 'Pitch session',
            self::Final => 'Final session',
            self::Showcase => 'Showcase',
        };
    }
}
