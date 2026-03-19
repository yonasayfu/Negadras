<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;

class SubmissionFilePolicy
{
    public function view(User $user, SubmissionFile $submissionFile): bool
    {
        return $user->can('submissions.view') || $submissionFile->submission->isOwnedBy($user);
    }

    public function create(User $user, Submission $submission): bool
    {
        return $user->can('submissions.update')
            || ($submission->isOwnedBy($user) && $submission->isEditableByPresenter());
    }

    public function delete(User $user, SubmissionFile $submissionFile): bool
    {
        return $user->can('submissions.update')
            || ($submissionFile->submission->isOwnedBy($user) && $submissionFile->submission->isEditableByPresenter());
    }
}
