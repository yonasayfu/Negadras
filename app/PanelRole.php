<?php

namespace App;

enum PanelRole: string
{
    case Chair = 'chair';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Chair => 'Chair',
            self::Member => 'Member',
        };
    }
}
