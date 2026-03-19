<?php

namespace App\Policies;

use App\Models\ReviewDecision;
use App\Models\User;

class ReviewDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submissions.view');
    }

    public function view(User $user, ReviewDecision $reviewDecision): bool
    {
        return $user->can('submissions.view')
            || $reviewDecision->submission?->applicant?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.update');
    }

    public function update(User $user, ReviewDecision $reviewDecision): bool
    {
        return $user->can('submissions.update');
    }

    public function delete(User $user, ReviewDecision $reviewDecision): bool
    {
        return false;
    }
}
