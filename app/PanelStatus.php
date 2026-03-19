<?php

namespace App;

enum PanelStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Active => 'published',
            self::Closed => 'archived',
        };
    }
}
