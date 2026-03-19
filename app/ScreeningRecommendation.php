<?php

namespace App;

enum ScreeningRecommendation: string
{
    case Pass = 'pass';
    case Reject = 'reject';
    case ReturnForRevision = 'return_for_revision';
    case Escalate = 'escalate';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Pass',
            self::Reject => 'Reject',
            self::ReturnForRevision => 'Return for revision',
            self::Escalate => 'Escalate',
        };
    }
}
