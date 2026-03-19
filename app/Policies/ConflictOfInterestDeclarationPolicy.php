<?php

namespace App\Policies;

use App\Models\ConflictOfInterestDeclaration;
use App\Models\User;

class ConflictOfInterestDeclarationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('panel-scoring.view') || $user->can('judge-workspace.view');
    }

    public function view(User $user, ConflictOfInterestDeclaration $conflictOfInterestDeclaration): bool
    {
        if ($user->can('panel-scoring.view')) {
            return true;
        }

        return $user->can('judge-workspace.view')
            && $conflictOfInterestDeclaration->judge?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('judge-conflicts.create');
    }

    public function update(User $user, ConflictOfInterestDeclaration $conflictOfInterestDeclaration): bool
    {
        return $user->can('judge-conflicts.update');
    }
}
