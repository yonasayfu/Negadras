<?php

namespace App;

enum JudgeCommentType: string
{
    case Private = 'private';
    case Internal = 'internal';
    case PresenterVisible = 'presenter_visible';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Internal => 'Internal',
            self::PresenterVisible => 'Presenter visible',
            self::Public => 'Public',
        };
    }
}
