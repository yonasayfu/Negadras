<?php

namespace App;

enum ExportJobStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Failed => 'destructive',
        };
    }
}
