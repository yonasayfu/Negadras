<?php

namespace App\Policies;

use App\Models\ScreeningReview;
use App\Models\User;

class ScreeningReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('screening-reviews.view');
    }

    public function view(User $user, ScreeningReview $screeningReview): bool
    {
        return $user->can('screening-reviews.view')
            || $screeningReview->reviewerAssignment?->isOwnedBy($user) === true;
    }

    public function create(User $user): bool
    {
        return $user->can('screening-reviews.create');
    }

    public function update(User $user, ScreeningReview $screeningReview): bool
    {
        if ($user->can('screening-reviews.update')) {
            return true;
        }

        return $screeningReview->reviewerAssignment?->isOwnedBy($user) === true
            && ! $screeningReview->isSubmitted();
    }

    public function delete(User $user, ScreeningReview $screeningReview): bool
    {
        return false;
    }
}
