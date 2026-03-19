<?php

namespace App\Policies;

use App\Models\Judge;
use App\Models\User;

class JudgePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('judges.view');
    }

    public function view(User $user, Judge $judge): bool
    {
        return $user->can('judges.view');
    }

    public function create(User $user): bool
    {
        return $user->can('judges.create');
    }

    public function update(User $user, Judge $judge): bool
    {
        return $user->can('judges.update');
    }
}
