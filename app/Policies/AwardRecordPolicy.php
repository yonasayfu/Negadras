<?php

namespace App\Policies;

use App\Models\AwardRecord;
use App\Models\User;

class AwardRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('awards.view');
    }

    public function view(User $user, AwardRecord $awardRecord): bool
    {
        return $user->can('awards.view');
    }

    public function create(User $user): bool
    {
        return $user->can('awards.update');
    }

    public function update(User $user, AwardRecord $awardRecord): bool
    {
        return $user->can('awards.update');
    }
}
