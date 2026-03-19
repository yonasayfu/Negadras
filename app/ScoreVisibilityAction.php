<?php

namespace App;

enum ScoreVisibilityAction: string
{
    case Reveal = 'reveal';
    case Hide = 'hide';

    public function label(): string
    {
        return match ($this) {
            self::Reveal => 'Reveal',
            self::Hide => 'Hide',
        };
    }
}
