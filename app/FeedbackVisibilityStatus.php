<?php

namespace App;

enum FeedbackVisibilityStatus: string
{
    case Draft = 'draft';
    case InternalReview = 'internal_review';
    case PresenterVisible = 'presenter_visible';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InternalReview => 'Internal review',
            self::PresenterVisible => 'Presenter visible',
        };
    }
}
