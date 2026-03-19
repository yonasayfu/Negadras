<?php

namespace App\Policies;

use App\Models\ArchiveRecord;
use App\Models\User;

class ArchiveRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('archive.view');
    }

    public function view(User $user, ArchiveRecord $archiveRecord): bool
    {
        return $user->can('archive.view');
    }

    public function create(User $user): bool
    {
        return $user->can('archive.update');
    }

    public function update(User $user, ArchiveRecord $archiveRecord): bool
    {
        return $user->can('archive.update');
    }
}
