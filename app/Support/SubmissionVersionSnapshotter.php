<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmissionVersionSnapshotter
{
    public function createSnapshot(Submission $submission, ?User $actor = null, ?string $changeNote = null): SubmissionVersion
    {
        return DB::transaction(function () use ($submission, $actor, $changeNote): SubmissionVersion {
            $submission->loadMissing([
                'season:id,name,year',
                'currentStage:id,name,code',
                'industry:id,name',
                'applicant:id,full_name,email',
                'organization:id,display_name',
            ]);

            $nextVersionNumber = (int) $submission->versions()->max('version_no') + 1;

            $version = $submission->versions()->create([
                'version_no' => $nextVersionNumber,
                'snapshot_json' => $this->snapshotPayload($submission),
                'change_note' => $changeNote,
                'created_by' => $actor?->id,
                'is_locked' => true,
            ]);

            $submission->forceFill([
                'current_version_id' => $version->id,
            ])->save();

            return $version;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotPayload(Submission $submission): array
    {
        return [
            'title' => $submission->title,
            'summary' => $submission->summary,
            'problem_statement' => $submission->problem_statement,
            'solution_description' => $submission->solution_description,
            'business_model' => $submission->business_model,
            'status' => $submission->status->value,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'is_public_after_approval' => $submission->is_public_after_approval,
            'season' => [
                'id' => $submission->season?->id,
                'name' => $submission->season?->name,
                'year' => $submission->season?->year,
            ],
            'stage' => [
                'id' => $submission->currentStage?->id,
                'name' => $submission->currentStage?->name,
                'code' => $submission->currentStage?->code,
            ],
            'industry' => [
                'id' => $submission->industry?->id,
                'name' => $submission->industry?->name,
            ],
            'applicant' => [
                'id' => $submission->applicant?->id,
                'full_name' => $submission->applicant?->full_name,
                'email' => $submission->applicant?->email,
            ],
            'organization' => [
                'id' => $submission->organization?->id,
                'display_name' => $submission->organization?->display_name,
            ],
        ];
    }
}
