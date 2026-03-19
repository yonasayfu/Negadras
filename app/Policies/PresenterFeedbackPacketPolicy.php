<?php

namespace App\Policies;

use App\FeedbackVisibilityStatus;
use App\Models\PresenterFeedbackPacket;
use App\Models\User;

class PresenterFeedbackPacketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('feedback-packets.view');
    }

    public function view(User $user, PresenterFeedbackPacket $presenterFeedbackPacket): bool
    {
        return $user->can('feedback-packets.view')
            || (
                $presenterFeedbackPacket->visibility_status === FeedbackVisibilityStatus::PresenterVisible
                && $presenterFeedbackPacket->submission?->applicant?->user_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->can('feedback-packets.update');
    }

    public function update(User $user, PresenterFeedbackPacket $presenterFeedbackPacket): bool
    {
        return $user->can('feedback-packets.update');
    }
}
