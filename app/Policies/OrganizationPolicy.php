<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organizations.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->can('organizations.view') || $organization->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('organizations.create') || $user->applicant !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can('organizations.update') || $organization->isManagedBy($user);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->can('organizations.delete');
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $user->can('organizations.delete');
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}
