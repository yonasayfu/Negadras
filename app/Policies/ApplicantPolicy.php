<?php

namespace App\Policies;

use App\Models\Applicant;
use App\Models\User;

class ApplicantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('applicants.view');
    }

    public function view(User $user, Applicant $applicant): bool
    {
        return $user->can('applicants.view') || $applicant->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Applicant $applicant): bool
    {
        return $user->can('applicants.update') || $applicant->user_id === $user->id;
    }

    public function delete(User $user, Applicant $applicant): bool
    {
        return $user->can('applicants.delete');
    }
}
