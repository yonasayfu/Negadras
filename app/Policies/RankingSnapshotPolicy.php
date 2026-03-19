<?php

namespace App\Policies;

use App\Models\RankingSnapshot;
use App\Models\User;

class RankingSnapshotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rankings.view');
    }

    public function view(User $user, RankingSnapshot $rankingSnapshot): bool
    {
        return $user->can('rankings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rankings.update');
    }

    public function update(User $user, RankingSnapshot $rankingSnapshot): bool
    {
        return $user->can('rankings.update');
    }
}
