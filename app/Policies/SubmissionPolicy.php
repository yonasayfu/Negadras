<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->can('submissions.view') || $submission->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->can('submissions.update')
            || ($submission->isOwnedBy($user) && $submission->status->allowsPresenterEdits());
    }

    public function submit(User $user, Submission $submission): bool
    {
        return $user->can('submissions.update')
            || ($submission->isOwnedBy($user) && $submission->status->allowsPresenterEdits());
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->can('submissions.delete')
            || ($submission->isOwnedBy($user) && $submission->status === SubmissionStatus::Draft);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Submission $submission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Submission $submission): bool
    {
        return false;
    }
}
