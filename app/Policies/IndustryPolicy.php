<?php

namespace App\Policies;

use App\Models\Industry;
use App\Models\User;

class IndustryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('industries.view');
    }

    public function view(User $user, Industry $industry): bool
    {
        return $user->can('industries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('industries.create');
    }

    public function update(User $user, Industry $industry): bool
    {
        return $user->can('industries.update');
    }

    public function delete(User $user, Industry $industry): bool
    {
        return $user->can('industries.delete');
    }
}
