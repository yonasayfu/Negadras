<?php

namespace App;

enum ReviewDecisionType: string
{
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case ReturnedForRevision = 'returned_for_revision';
    case NeedsMoreReview = 'needs_more_review';

    public function label(): string
    {
        return match ($this) {
            self::Shortlisted => 'Shortlisted',
            self::Rejected => 'Rejected',
            self::ReturnedForRevision => 'Returned for revision',
            self::NeedsMoreReview => 'Needs more review',
        };
    }
}
