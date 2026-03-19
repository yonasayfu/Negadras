<?php

namespace App\Policies;

use App\Models\PanelSubmissionAssignment;
use App\Models\ScoreEntry;
use App\Models\User;

class ScoreEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('panel-scoring.view') || $user->can('judge-workspace.view');
    }

    public function view(User $user, ScoreEntry $scoreEntry): bool
    {
        if ($user->can('panel-scoring.view')) {
            return true;
        }

        return $user->can('judge-workspace.view')
            && $scoreEntry->judge?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('judge-scores.create');
    }

    public function update(User $user, ScoreEntry $scoreEntry): bool
    {
        return $user->can('judge-scores.create')
            && $scoreEntry->judge?->user_id === $user->id;
    }

    public function lock(User $user, PanelSubmissionAssignment $assignment): bool
    {
        return $user->can('panel-scoring.update');
    }
}
