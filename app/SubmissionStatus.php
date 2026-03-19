<?php

namespace App;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderIntakeCheck = 'under_intake_check';
    case IncompleteReturned = 'incomplete_returned';
    case Eligible = 'eligible';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderIntakeCheck => 'Under intake check',
            self::IncompleteReturned => 'Returned for correction',
            self::Eligible => 'Eligible',
            self::Rejected => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Submitted => 'review',
            self::UnderIntakeCheck => 'review',
            self::IncompleteReturned => 'review',
            self::Eligible => 'published',
            self::Rejected => 'archived',
        };
    }

    public function allowsPresenterEdits(): bool
    {
        return match ($this) {
            self::Draft, self::IncompleteReturned => true,
            self::Submitted, self::UnderIntakeCheck, self::Eligible, self::Rejected => false,
        };
    }
}
