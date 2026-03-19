<?php

namespace App\Policies;

use App\Models\CompetitionSession;
use App\Models\User;

class CompetitionSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('competition-sessions.view') || $user->can('live-operations.view');
    }

    public function view(User $user, CompetitionSession $competitionSession): bool
    {
        return $user->can('competition-sessions.view') || $user->can('live-operations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('competition-sessions.create');
    }

    public function update(User $user, CompetitionSession $competitionSession): bool
    {
        return $user->can('competition-sessions.update') || $user->can('live-operations.update');
    }

    public function delete(User $user, CompetitionSession $competitionSession): bool
    {
        return $user->can('competition-sessions.update');
    }
}
