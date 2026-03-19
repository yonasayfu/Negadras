<?php

namespace App\Policies;

use App\Models\TechnicalReview;
use App\Models\User;

class TechnicalReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('technical-reviews.view');
    }

    public function view(User $user, TechnicalReview $technicalReview): bool
    {
        return $user->can('technical-reviews.view')
            || $technicalReview->reviewerAssignment?->isOwnedBy($user) === true;
    }

    public function create(User $user): bool
    {
        return $user->can('technical-reviews.create');
    }

    public function update(User $user, TechnicalReview $technicalReview): bool
    {
        if ($user->can('technical-reviews.update')) {
            return true;
        }

        return $technicalReview->reviewerAssignment?->isOwnedBy($user) === true
            && ! $technicalReview->isSubmitted();
    }

    public function delete(User $user, TechnicalReview $technicalReview): bool
    {
        return false;
    }
}
