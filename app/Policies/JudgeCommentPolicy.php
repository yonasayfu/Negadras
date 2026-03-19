<?php

namespace App\Policies;

use App\Models\JudgeComment;
use App\Models\User;

class JudgeCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('panel-scoring.view') || $user->can('judge-workspace.view');
    }

    public function view(User $user, JudgeComment $judgeComment): bool
    {
        if ($user->can('panel-scoring.view')) {
            return true;
        }

        return $user->can('judge-workspace.view')
            && $judgeComment->judge?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('judge-scores.create');
    }
}
