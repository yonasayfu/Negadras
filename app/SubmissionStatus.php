<?php

namespace App;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case IncompleteReturned = 'incomplete_returned';
    case Eligible = 'eligible';
    case ScreeningRejected = 'screening_rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::IncompleteReturned => 'Returned for correction',
            self::Eligible => 'Eligible',
            self::ScreeningRejected => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Submitted => 'review',
            self::IncompleteReturned => 'review',
            self::Eligible => 'published',
            self::ScreeningRejected => 'archived',
        };
    }

    public function allowsPresenterEdits(): bool
    {
        return match ($this) {
            self::Draft, self::IncompleteReturned => true,
            self::Submitted, self::Eligible, self::ScreeningRejected => false,
        };
    }
}
