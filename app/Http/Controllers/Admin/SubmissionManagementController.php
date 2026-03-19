<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionSubmissionStatusRequest;
use App\Models\Industry;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\SubmissionFileRegistry;
use App\Support\SubmissionIntakeChecklist;
use App\Support\SubmissionStatusTransitionService;
use Illuminate\Http\RedirectResponse;
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
        $seasonId = $request->integer('season_id');
        $stageId = $request->integer('stage_id');
        $industryId = $request->integer('industry_id');
        $status = $request->string('status')->trim()->toString();
        $transitionService = app(SubmissionStatusTransitionService::class);
        $checklist = app(SubmissionIntakeChecklist::class);

        return Inertia::render('admin/Submissions/Index', [
            'submissions' => Submission::query()
                ->with([
                    'season:id,name',
                    'currentStage:id,name',
                    'industry:id,name',
                    'applicant:id,full_name,email,phone',
                    'organization:id,display_name,contact_email',
                    'organization.teamMembers:id,organization_id,is_primary_contact',
                    'files:id,submission_id,file_type',
                    'statusHistory:id,submission_id,from_status,to_status,changed_by,reason,created_at',
                ])
                ->when($seasonId > 0, fn ($query) => $query->where('season_id', $seasonId))
                ->when($stageId > 0, fn ($query) => $query->where('current_stage_id', $stageId))
                ->when($industryId > 0, fn ($query) => $query->where('industry_id', $industryId))
                ->when($status !== '', fn ($query) => $query->where('status', $status))
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
                ->through(fn (Submission $submission): array => $this->submissionSummary($submission, $checklist, $transitionService)),
            'filters' => [
                'search' => $search,
                'seasonId' => $seasonId > 0 ? (string) $seasonId : '',
                'stageId' => $stageId > 0 ? (string) $stageId : '',
                'industryId' => $industryId > 0 ? (string) $industryId : '',
                'status' => $status,
            ],
            'seasonOptions' => Season::query()
                ->orderByDesc('year')
                ->get(['id', 'name', 'year'])
                ->map(fn (Season $season): array => [
                    'value' => (string) $season->id,
                    'label' => sprintf('%s (%s)', $season->name, $season->year),
                ])
                ->all(),
            'stageOptions' => Stage::query()
                ->with('season:id,name')
                ->orderBy('season_id')
                ->orderBy('order_index')
                ->get()
                ->map(fn (Stage $stage): array => [
                    'value' => (string) $stage->id,
                    'label' => sprintf('%s - %s', $stage->season?->name ?? 'Season', $stage->name),
                ])
                ->all(),
            'industryOptions' => Industry::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Industry $industry): array => [
                    'value' => (string) $industry->id,
                    'label' => $industry->name,
                ])
                ->all(),
            'statusOptions' => collect([
                SubmissionStatus::Draft,
                SubmissionStatus::Submitted,
                SubmissionStatus::UnderIntakeCheck,
                SubmissionStatus::IncompleteReturned,
                SubmissionStatus::Eligible,
                SubmissionStatus::Rejected,
            ])->map(fn (SubmissionStatus $submissionStatus): array => [
                'value' => $submissionStatus->value,
                'label' => $submissionStatus->label(),
            ])->all(),
        ]);
    }

    public function show(Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load([
            'season:id,name',
            'currentStage:id,name',
            'industry:id,name',
            'applicant:id,full_name,email,phone',
            'organization:id,display_name,contact_email',
            'organization.teamMembers:id,organization_id,is_primary_contact',
            'reviewerAssignments.reviewer.user:id,name,email',
            'reviewerAssignments.stage:id,name',
            'reviewerAssignments.screeningReview:id,reviewer_assignment_id,recommendation,submitted_at',
            'currentVersion:id,submission_id,version_no,created_by,change_note,is_locked,created_at',
            'versions.creator:id,name',
            'files.version:id,version_no',
            'files.uploadedBy:id,name',
            'statusHistory.actor:id,name',
        ]);

        return Inertia::render('admin/Submissions/Show', [
            'submission' => [
                ...$this->submissionSummary(
                    $submission,
                    app(SubmissionIntakeChecklist::class),
                    app(SubmissionStatusTransitionService::class),
                ),
                'summary' => $submission->summary,
                'problemStatement' => $submission->problem_statement,
                'solutionDescription' => $submission->solution_description,
                'businessModel' => $submission->business_model,
                'applicantName' => $submission->applicant?->full_name,
                'applicantEmail' => $submission->applicant?->email,
                'isPublicAfterApproval' => $submission->is_public_after_approval,
                'currentVersionNumber' => $submission->currentVersion?->version_no,
                'versionCount' => $submission->versions->count(),
                'latestStatusReason' => $submission->statusHistory->first()?->reason,
                'draftFiles' => $submission->files
                    ->whereNull('submission_version_id')
                    ->map(fn (SubmissionFile $file): array => $this->submissionFileSummary($file))
                    ->values()
                    ->all(),
                'currentVersionFiles' => $submission->files
                    ->where('submission_version_id', $submission->current_version_id)
                    ->map(fn (SubmissionFile $file): array => $this->submissionFileSummary($file))
                    ->values()
                    ->all(),
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
                'statusTimeline' => $submission->statusHistory
                    ->map(fn (SubmissionStatusHistory $entry): array => $this->statusTimelineEntry($entry))
                    ->values()
                    ->all(),
                'reviewerAssignments' => $submission->reviewerAssignments
                    ->map(fn (ReviewerAssignment $assignment): array => [
                        'id' => $assignment->id,
                        'reviewerName' => $assignment->reviewer?->user?->name,
                        'reviewerEmail' => $assignment->reviewer?->user?->email,
                        'stageName' => $assignment->stage?->name,
                        'status' => $assignment->status->value,
                        'statusLabel' => $assignment->status->label(),
                        'statusTone' => $assignment->status->tone(),
                        'assignedAt' => $assignment->assigned_at?->toDateTimeString(),
                        'dueAt' => $assignment->due_at?->toDateTimeString(),
                        'recommendationLabel' => $assignment->screeningReview?->recommendation?->label(),
                    ])
                    ->values()
                    ->all(),
            ],
            'submissionFileDefinitions' => $this->submissionFileDefinitions(),
            'availableTransitions' => app(SubmissionStatusTransitionService::class)->availableIntakeTransitions($submission),
            'canTransitionStatus' => request()->user()?->can('update', $submission) ?? false,
            'reviewerOptions' => Reviewer::query()
                ->with('user:id,name,email')
                ->withCount(['assignments as activeAssignmentsCount' => function ($query): void {
                    $query->whereIn('status', [
                        ReviewerAssignmentStatus::Assigned,
                        ReviewerAssignmentStatus::InProgress,
                    ]);
                }])
                ->where('is_active', true)
                ->orderBy(
                    User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'reviewers.user_id')
                        ->limit(1),
                )
                ->get()
                ->map(fn (Reviewer $reviewer): array => [
                    'value' => (string) $reviewer->id,
                    'label' => sprintf(
                        '%s (%s active)',
                        $reviewer->user?->name ?? 'Reviewer',
                        $reviewer->activeAssignmentsCount,
                    ),
                ])
                ->all(),
        ]);
    }

    public function transition(
        TransitionSubmissionStatusRequest $request,
        Submission $submission,
        SubmissionStatusTransitionService $statusTransitions,
    ): RedirectResponse {
        $this->authorize('update', $submission);

        $toStatus = SubmissionStatus::from($request->validated('status'));

        $statusTransitions->transition(
            submission: $submission,
            toStatus: $toStatus,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.submissions.status-transitioned',
            description: "Moved {$submission->title} to {$toStatus->label()}.",
            subject: $submission,
            properties: [
                'to_status' => $toStatus->value,
                'reason' => $request->validated('reason'),
            ],
            request: $request,
        );

        return to_route('admin-submissions.show', $submission)->with('success', 'Submission status updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionSummary(
        Submission $submission,
        SubmissionIntakeChecklist $checklist,
        SubmissionStatusTransitionService $transitionService,
    ): array {
        $intakeChecklist = $checklist->forSubmission($submission);

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
            'latestStatusReason' => $submission->statusHistory->first()?->reason,
            'intakeChecklist' => $intakeChecklist,
            'availableTransitions' => $transitionService->availableIntakeTransitions($submission),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionFileSummary(SubmissionFile $file): array
    {
        return [
            'id' => $file->id,
            'fileType' => $file->file_type,
            'fileTypeLabel' => SubmissionFileRegistry::definition($file->file_type)['label'] ?? $file->file_type,
            'originalName' => $file->original_name,
            'mimeType' => $file->mime_type,
            'fileSize' => $file->file_size,
            'description' => $file->description,
            'downloadUrl' => route('submission-files.download', $file),
            'isRequired' => $file->is_required,
            'isVerified' => $file->is_verified,
            'uploadedAt' => $file->uploaded_at?->toDateTimeString(),
            'uploadedBy' => $file->uploadedBy?->name,
            'versionNumber' => $file->version?->version_no,
        ];
    }

    /**
     * @return array<int, array{type: string, label: string, description: string, required: bool, multiple: bool, accept: string}>
     */
    private function submissionFileDefinitions(): array
    {
        return collect(SubmissionFileRegistry::definitions())
            ->map(fn (array $definition, string $type): array => [
                'type' => $type,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'required' => $definition['required'],
                'multiple' => $definition['multiple'],
                'accept' => $definition['accept'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function statusTimelineEntry(SubmissionStatusHistory $entry): array
    {
        return [
            'id' => $entry->id,
            'fromStatus' => $entry->from_status?->value,
            'fromStatusLabel' => $entry->from_status?->label(),
            'toStatus' => $entry->to_status->value,
            'toStatusLabel' => $entry->to_status->label(),
            'toStatusTone' => $entry->to_status->tone(),
            'reason' => $entry->reason,
            'changedAt' => $entry->created_at?->toDateTimeString(),
            'changedBy' => $entry->actor?->name,
        ];
    }
}
