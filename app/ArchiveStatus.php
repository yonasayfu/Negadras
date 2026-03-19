<?php

namespace App;

enum ArchiveStatus: string
{
    case Draft = 'draft';
    case Archived = 'archived';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Archived => 'Archived',
            self::Published => 'Published',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Archived => 'archived',
            self::Published => 'published',
        };
    }
}
