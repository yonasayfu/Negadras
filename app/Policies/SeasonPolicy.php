<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('seasons.view');
    }

    public function view(User $user, Season $season): bool
    {
        return $user->can('seasons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('seasons.create');
    }

    public function update(User $user, Season $season): bool
    {
        return $user->can('seasons.update');
    }

    public function delete(User $user, Season $season): bool
    {
        return $user->can('seasons.delete');
    }
}
