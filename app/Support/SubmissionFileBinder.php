<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\SubmissionVersion;

class SubmissionFileBinder
{
    public function bindDraftFilesToVersion(Submission $submission, SubmissionVersion $version): void
    {
        $submission->files()
            ->whereNull('submission_version_id')
            ->update([
                'submission_version_id' => $version->id,
            ]);
    }
}
