<?php

namespace App;

enum StageType: string
{
    case Registration = 'registration';
    case Intake = 'intake';
    case Screening = 'screening';
    case Review = 'review';
    case Live = 'live';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Intake => 'Intake',
            self::Screening => 'Screening',
            self::Review => 'Review',
            self::Live => 'Live',
            self::Final => 'Final',
        };
    }
}
