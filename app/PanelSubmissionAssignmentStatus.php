<?php

namespace App;

enum PanelSubmissionAssignmentStatus: string
{
    case Assigned = 'assigned';
    case Scoring = 'scoring';
    case Submitted = 'submitted';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Scoring => 'Scoring in progress',
            self::Submitted => 'Scores submitted',
            self::Locked => 'Locked',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Assigned, self::Scoring => 'review',
            self::Submitted => 'published',
            self::Locked => 'archived',
        };
    }
}
