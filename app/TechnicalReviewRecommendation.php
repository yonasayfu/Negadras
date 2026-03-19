<?php

namespace App;

enum TechnicalReviewRecommendation: string
{
    case Advance = 'advance';
    case NeedsRevision = 'needs_revision';
    case Reject = 'reject';
    case NeedsMoreReview = 'needs_more_review';

    public function label(): string
    {
        return match ($this) {
            self::Advance => 'Advance',
            self::NeedsRevision => 'Needs revision',
            self::Reject => 'Reject',
            self::NeedsMoreReview => 'Needs more review',
        };
    }
}
