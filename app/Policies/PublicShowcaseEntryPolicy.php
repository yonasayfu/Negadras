<?php

namespace App\Policies;

use App\Models\PublicShowcaseEntry;
use App\Models\User;

class PublicShowcaseEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('archive.view');
    }

    public function view(User $user, PublicShowcaseEntry $publicShowcaseEntry): bool
    {
        return $user->can('archive.view');
    }

    public function create(User $user): bool
    {
        return $user->can('archive.update');
    }

    public function update(User $user, PublicShowcaseEntry $publicShowcaseEntry): bool
    {
        return $user->can('archive.update');
    }
}
