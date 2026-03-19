<?php

namespace App;

enum ScreeningEligibilityStatus: string
{
    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
    case NeedsClarification = 'needs_clarification';

    public function label(): string
    {
        return match ($this) {
            self::Eligible => 'Eligible',
            self::Ineligible => 'Ineligible',
            self::NeedsClarification => 'Needs clarification',
        };
    }
}
