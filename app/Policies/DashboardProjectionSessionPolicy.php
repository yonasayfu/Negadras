<?php

namespace App\Policies;

use App\Models\DashboardProjectionSession;
use App\Models\User;

class DashboardProjectionSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('live-operations.view');
    }

    public function view(User $user, DashboardProjectionSession $dashboardProjectionSession): bool
    {
        return $user->can('live-operations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('live-operations.update');
    }

    public function update(User $user, DashboardProjectionSession $dashboardProjectionSession): bool
    {
        return $user->can('live-operations.update');
    }

    public function delete(User $user, DashboardProjectionSession $dashboardProjectionSession): bool
    {
        return $user->can('live-operations.update');
    }
}
