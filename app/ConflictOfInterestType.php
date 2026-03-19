<?php

namespace App;

enum ConflictOfInterestType: string
{
    case Personal = 'personal';
    case Organization = 'organization';
    case PriorInvolvement = 'prior_involvement';
    case SelfDeclared = 'self_declared';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal relationship',
            self::Organization => 'Organization relationship',
            self::PriorInvolvement => 'Prior involvement',
            self::SelfDeclared => 'Self declared',
        };
    }
}
