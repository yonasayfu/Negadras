<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Submission::class);

        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/Submissions/Index', [
            'submissions' => Submission::query()
                ->with([
                    'season:id,name',
                    'currentStage:id,name',
                    'industry:id,name',
                    'applicant:id,full_name,email',
                    'organization:id,display_name',
                ])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($submissionQuery) use ($search): void {
                        $submissionQuery
                            ->where('title', 'ilike', "%{$search}%")
                            ->orWhereHas('applicant', function ($applicantQuery) use ($search): void {
                                $applicantQuery
                                    ->where('full_name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            })
                            ->orWhereHas('organization', function ($organizationQuery) use ($search): void {
                                $organizationQuery
                                    ->where('display_name', 'ilike', "%{$search}%")
                                    ->orWhere('legal_name', 'ilike', "%{$search}%");
                            });
                    });
                })
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Submission $submission): array => $this->submissionSummary($submission)),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load([
            'season:id,name',
            'currentStage:id,name',
            'industry:id,name',
            'applicant:id,full_name,email',
            'organization:id,display_name',
            'currentVersion:id,submission_id,version_no,created_by,change_note,is_locked,created_at',
            'versions.creator:id,name',
        ]);

        return Inertia::render('admin/Submissions/Show', [
            'submission' => [
                ...$this->submissionSummary($submission),
                'summary' => $submission->summary,
                'problemStatement' => $submission->problem_statement,
                'solutionDescription' => $submission->solution_description,
                'businessModel' => $submission->business_model,
                'applicantName' => $submission->applicant?->full_name,
                'applicantEmail' => $submission->applicant?->email,
                'isPublicAfterApproval' => $submission->is_public_after_approval,
                'currentVersionNumber' => $submission->currentVersion?->version_no,
                'versionCount' => $submission->versions->count(),
                'versionHistory' => $submission->versions
                    ->map(fn ($version): array => [
                        'id' => $version->id,
                        'versionNo' => $version->version_no,
                        'changeNote' => $version->change_note,
                        'createdAt' => $version->created_at?->toDateTimeString(),
                        'createdBy' => $version->creator?->name,
                        'isLocked' => $version->is_locked,
                        'isCurrent' => $submission->current_version_id === $version->id,
                        'snapshotTitle' => $version->snapshot_json['title'] ?? $submission->title,
                        'snapshotStatus' => Str::of($version->snapshot_json['status'] ?? 'draft')->replace('_', ' ')->title()->toString(),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionSummary(Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'seasonName' => $submission->season?->name,
            'stageName' => $submission->currentStage?->name,
            'industryName' => $submission->industry?->name,
            'organizationName' => $submission->organization?->display_name,
            'applicantName' => $submission->applicant?->full_name,
            'applicantEmail' => $submission->applicant?->email,
            'status' => $submission->status->value,
            'statusLabel' => $submission->status->label(),
            'statusTone' => $submission->status->tone(),
            'submittedAt' => $submission->submitted_at?->toDateTimeString(),
            'updatedAt' => $submission->updated_at?->toDateTimeString(),
        ];
    }
}
