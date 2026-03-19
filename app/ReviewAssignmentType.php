<?php

namespace App;

enum ReviewAssignmentType: string
{
    case Screening = 'screening';
    case Technical = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::Screening => 'Screening',
            self::Technical => 'Technical review',
        };
    }
}
