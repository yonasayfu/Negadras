<?php

namespace App;

enum AwardType: string
{
    case Winner = 'winner';
    case RunnerUp = 'runner_up';
    case Finalist = 'finalist';
    case HonorableMention = 'honorable_mention';
    case AudienceChoice = 'audience_choice';

    public function label(): string
    {
        return match ($this) {
            self::Winner => 'Winner',
            self::RunnerUp => 'Runner-up',
            self::Finalist => 'Finalist',
            self::HonorableMention => 'Honorable mention',
            self::AudienceChoice => 'Audience choice',
        };
    }
}
