<?php

namespace App\Policies;

use App\Models\ShortlistRecord;
use App\Models\User;

class ShortlistRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submissions.view');
    }

    public function view(User $user, ShortlistRecord $shortlistRecord): bool
    {
        return $user->can('submissions.view')
            || $shortlistRecord->submission?->applicant?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.update');
    }

    public function update(User $user, ShortlistRecord $shortlistRecord): bool
    {
        return $user->can('submissions.update');
    }

    public function delete(User $user, ShortlistRecord $shortlistRecord): bool
    {
        return false;
    }
}
