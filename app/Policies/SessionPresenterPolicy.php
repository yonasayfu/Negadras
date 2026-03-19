<?php

namespace App\Policies;

use App\Models\SessionPresenter;
use App\Models\User;

class SessionPresenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('competition-sessions.view') || $user->can('live-operations.view');
    }

    public function view(User $user, SessionPresenter $sessionPresenter): bool
    {
        return $user->can('competition-sessions.view') || $user->can('live-operations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('competition-sessions.update') || $user->can('live-operations.update');
    }

    public function update(User $user, SessionPresenter $sessionPresenter): bool
    {
        return $user->can('competition-sessions.update') || $user->can('live-operations.update');
    }

    public function delete(User $user, SessionPresenter $sessionPresenter): bool
    {
        return $user->can('competition-sessions.update');
    }
}
