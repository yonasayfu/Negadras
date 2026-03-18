<?php

namespace App;

enum ApplicantType: string
{
    case Individual = 'individual';
    case Team = 'team';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Team => 'Team',
            self::Organization => 'Organization',
        };
    }
}
