<?php

namespace App\Http\Controllers;

use App\Models\ReviewerAssignment;
use App\Models\SubmissionFile;
use App\Models\SubmissionStatusHistory;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use App\Support\ReviewerAssignmentOverdueService;
use App\Support\SubmissionFileRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReviewerQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReviewerAssignment::class);
        app(ReviewerAssignmentOverdueService::class)->expireOverdueAssignments();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $stageId = $request->integer('stage_id');

        $reviewer = $request->user()?->reviewer;

        abort_if($reviewer === null || ! $reviewer->is_active, 403);

        return Inertia::render('reviewers/Queue', [
            'assignments' => ReviewerAssignment::query()
                ->with([
                    'stage:id,name',
                    'submission:id,title,season_id,current_stage_id,industry_id,applicant_id,organization_id,status,updated_at,submitted_at',
                    'submission.season:id,name',
                    'submission.industry:id,name',
                    'submission.applicant:id,full_name,email',
                    'submission.organization:id,display_name',
                    'screeningReview:id,reviewer_assignment_id,recommendation,submitted_at',
                ])
                ->where('reviewer_id', $reviewer->id)
                ->where('assignment_type', ReviewAssignmentType::Screening)
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($stageId > 0, fn ($query) => $query->where('stage_id', $stageId))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->whereHas('submission', function ($submissionQuery) use ($search): void {
                        $submissionQuery
                            ->where('title', 'ilike', "%{$search}%")
                            ->orWhereHas('applicant', function ($applicantQuery) use ($search): void {
                                $applicantQuery
                                    ->where('full_name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            });
                    });
                })
                ->latest('assigned_at')
                ->get()
                ->map(fn (ReviewerAssignment $assignment): array => $this->assignmentSummary($assignment))
                ->values()
                ->all(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'stageId' => $stageId > 0 ? (string) $stageId : '',
            ],
            'statusOptions' => collect(ReviewerAssignmentStatus::cases())->map(fn ($statusCase): array => [
                'value' => $statusCase->value,
                'label' => $statusCase->label(),
            ])->all(),
            'stageOptions' => ReviewerAssignment::query()
                ->where('reviewer_id', $reviewer->id)
                ->where('assignment_type', ReviewAssignmentType::Screening)
                ->with('stage:id,name')
                ->get()
                ->pluck('stage')
                ->filter()
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->map(fn ($stage): array => [
                    'value' => (string) $stage->id,
                    'label' => $stage->name,
                ])
                ->all(),
        ]);
    }

    public function show(ReviewerAssignment $reviewerAssignment): Response
    {
        $this->authorize('view', $reviewerAssignment);
        app(ReviewerAssignmentOverdueService::class)->expireOverdueAssignments();

        abort_unless($reviewerAssignment->isScreening(), 404);

        $reviewerAssignment->load([
            'reviewer.user:id,name,email',
            'stage:id,name',
            'screeningReview',
            'submission.season:id,name',
            'submission.currentStage:id,name',
            'submission.industry:id,name',
            'submission.applicant:id,full_name,email,phone',
            'submission.organization:id,display_name,contact_email',
            'submission.currentVersion:id,submission_id,version_no,created_by,change_note,is_locked,created_at',
            'submission.versions.creator:id,name',
            'submission.files.version:id,version_no',
            'submission.files.uploadedBy:id,name',
            'submission.statusHistory.actor:id,name',
        ]);

        $submission = $reviewerAssignment->submission;

        return Inertia::render('reviewers/Review', [
            'assignment' => $this->assignmentDetail($reviewerAssignment),
            'submissionFileDefinitions' => collect(SubmissionFileRegistry::definitions())
                ->map(fn (array $definition, string $type): array => [
                    'type' => $type,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'required' => $definition['required'],
                    'multiple' => $definition['multiple'],
                    'accept' => $definition['accept'],
                ])
                ->values()
                ->all(),
            'submission' => [
                'id' => $submission->id,
                'title' => $submission->title,
                'seasonName' => $submission->season?->name,
                'stageName' => $submission->currentStage?->name,
                'industryName' => $submission->industry?->name,
                'organizationName' => $submission->organization?->display_name,
                'applicantName' => $submission->applicant?->full_name,
                'applicantEmail' => $submission->applicant?->email,
                'statusLabel' => $submission->status->label(),
                'statusTone' => $submission->status->tone(),
                'summary' => $submission->summary,
                'problemStatement' => $submission->problem_statement,
                'solutionDescription' => $submission->solution_description,
                'businessModel' => $submission->business_model,
                'currentVersionNumber' => $submission->currentVersion?->version_no,
                'latestStatusReason' => $submission->statusHistory->first()?->reason,
                'intakeNotes' => $submission->statusHistory
                    ->filter(fn (SubmissionStatusHistory $entry): bool => filled($entry->reason))
                    ->take(5)
                    ->map(fn (SubmissionStatusHistory $entry): array => [
                        'statusLabel' => $entry->to_status->label(),
                        'reason' => $entry->reason,
                        'changedAt' => $entry->created_at?->toDateTimeString(),
                        'changedBy' => $entry->actor?->name,
                    ])
                    ->values()
                    ->all(),
                'currentVersionFiles' => $submission->files
                    ->where('submission_version_id', $submission->current_version_id)
                    ->map(fn (SubmissionFile $file): array => [
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
                    ])
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
                    ->map(fn (SubmissionStatusHistory $entry): array => [
                        'id' => $entry->id,
                        'fromStatus' => $entry->from_status?->value,
                        'fromStatusLabel' => $entry->from_status?->label(),
                        'toStatus' => $entry->to_status->value,
                        'toStatusLabel' => $entry->to_status->label(),
                        'toStatusTone' => $entry->to_status->tone(),
                        'reason' => $entry->reason,
                        'changedAt' => $entry->created_at?->toDateTimeString(),
                        'changedBy' => $entry->actor?->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'recommendationOptions' => collect(ScreeningRecommendation::cases())->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'eligibilityOptions' => collect(ScreeningEligibilityStatus::cases())->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentSummary(ReviewerAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'submissionId' => $assignment->submission_id,
            'title' => $assignment->submission?->title,
            'seasonName' => $assignment->submission?->season?->name,
            'stageName' => $assignment->stage?->name,
            'industryName' => $assignment->submission?->industry?->name,
            'applicantName' => $assignment->submission?->applicant?->full_name,
            'status' => $assignment->status->value,
            'statusLabel' => $assignment->status->label(),
            'statusTone' => $assignment->status->tone(),
            'dueAt' => $assignment->due_at?->toDateTimeString(),
            'assignedAt' => $assignment->assigned_at?->toDateTimeString(),
            'isOverdue' => $assignment->due_at?->isPast() && $assignment->status->isActive(),
            'recommendationLabel' => $assignment->screeningReview?->recommendation?->label(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentDetail(ReviewerAssignment $assignment): array
    {
        return [
            ...$this->assignmentSummary($assignment),
            'reviewerName' => $assignment->reviewer?->user?->name,
            'reviewerEmail' => $assignment->reviewer?->user?->email,
            'review' => $assignment->screeningReview === null ? null : [
                'eligibilityStatus' => $assignment->screeningReview->eligibility_status?->value,
                'eligibilityChecklist' => $assignment->screeningReview->eligibility_checklist,
                'recommendation' => $assignment->screeningReview->recommendation?->value,
                'scoreOptional' => $assignment->screeningReview->score_optional,
                'notes' => $assignment->screeningReview->notes,
                'submittedAt' => $assignment->screeningReview->submitted_at?->toDateTimeString(),
            ],
        ];
    }
}
