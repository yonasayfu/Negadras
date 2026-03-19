<?php

namespace App\Policies;

use App\Models\Rubric;
use App\Models\User;

class RubricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rubrics.view');
    }

    public function view(User $user, Rubric $rubric): bool
    {
        return $user->can('rubrics.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rubrics.create');
    }

    public function update(User $user, Rubric $rubric): bool
    {
        return $user->can('rubrics.update');
    }
}
