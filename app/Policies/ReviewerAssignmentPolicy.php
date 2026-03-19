<?php

namespace App\Policies;

use App\Models\ReviewerAssignment;
use App\Models\User;

class ReviewerAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviewer-assignments.view')
            || $user->can('reviewer-queue.view')
            || $user->can('technical-reviewer-queue.view');
    }

    public function view(User $user, ReviewerAssignment $reviewerAssignment): bool
    {
        return $user->can('reviewer-assignments.view') || $reviewerAssignment->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('reviewer-assignments.create');
    }

    public function update(User $user, ReviewerAssignment $reviewerAssignment): bool
    {
        return $user->can('reviewer-assignments.update') || $reviewerAssignment->isOwnedBy($user);
    }

    public function delete(User $user, ReviewerAssignment $reviewerAssignment): bool
    {
        return $user->can('reviewer-assignments.update');
    }
}
