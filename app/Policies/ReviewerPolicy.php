<?php

namespace App\Policies;

use App\Models\Reviewer;
use App\Models\User;

class ReviewerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviewers.view');
    }

    public function view(User $user, Reviewer $reviewer): bool
    {
        return $user->can('reviewers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reviewers.create');
    }

    public function update(User $user, Reviewer $reviewer): bool
    {
        return $user->can('reviewers.update');
    }

    public function delete(User $user, Reviewer $reviewer): bool
    {
        return false;
    }
}
