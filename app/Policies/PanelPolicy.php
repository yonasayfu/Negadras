<?php

namespace App\Policies;

use App\Models\Panel;
use App\Models\User;

class PanelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('panels.view');
    }

    public function view(User $user, Panel $panel): bool
    {
        return $user->can('panels.view');
    }

    public function create(User $user): bool
    {
        return $user->can('panels.create');
    }

    public function update(User $user, Panel $panel): bool
    {
        return $user->can('panels.update');
    }
}
