<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransitionScreeningDecisionRequest;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Notifications\SystemMessageNotification;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\SubmissionStatusTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScreeningQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Submission::class);

        $search = $request->string('search')->trim()->toString();
        $reviewerId = $request->integer('reviewer_id');
        $stageId = $request->integer('stage_id');
        $industryId = $request->integer('industry_id');
        $recommendation = $request->string('recommendation')->trim()->toString();
        $queueState = $request->string('queue_state')->trim()->toString();

        return Inertia::render('admin/Screening/Index', [
            'submissions' => Submission::query()
                ->with([
                    'season:id,name',
                    'currentStage:id,name',
                    'industry:id,name',
                    'applicant:id,full_name,email',
                    'organization:id,display_name',
                    'reviewerAssignments:id,submission_id,reviewer_id,stage_id,status,assigned_at,due_at',
                    'reviewerAssignments.reviewer.user:id,name',
                    'reviewerAssignments.screeningReview:id,reviewer_assignment_id,recommendation,eligibility_status,submitted_at',
                    'statusHistory:id,submission_id,from_status,to_status,changed_by,reason,created_at',
                ])
                ->where('status', SubmissionStatus::Eligible)
                ->whereHas('reviewerAssignments')
                ->when($stageId > 0, fn ($query) => $query->where('current_stage_id', $stageId))
                ->when($industryId > 0, fn ($query) => $query->where('industry_id', $industryId))
                ->when($reviewerId > 0, fn ($query) => $query->whereHas('reviewerAssignments', fn ($assignmentQuery) => $assignmentQuery->where('reviewer_id', $reviewerId)))
                ->when($recommendation !== '', fn ($query) => $query->whereHas('screeningReviews', fn ($reviewQuery) => $reviewQuery->where('recommendation', $recommendation)->whereNotNull('submitted_at')))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($submissionQuery) use ($search): void {
                        $submissionQuery
                            ->where('title', 'ilike', "%{$search}%")
                            ->orWhereHas('applicant', function ($applicantQuery) use ($search): void {
                                $applicantQuery
                                    ->where('full_name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            })
                            ->orWhereHas('organization', fn ($organizationQuery) => $organizationQuery->where('display_name', 'ilike', "%{$search}%"));
                    });
                })
                ->get()
                ->filter(fn (Submission $submission): bool => $queueState === '' || $this->screeningState($submission) === $queueState)
                ->values()
                ->map(fn (Submission $submission): array => $this->screeningSummary($submission))
                ->all(),
            'filters' => [
                'search' => $search,
                'reviewerId' => $reviewerId > 0 ? (string) $reviewerId : '',
                'stageId' => $stageId > 0 ? (string) $stageId : '',
                'industryId' => $industryId > 0 ? (string) $industryId : '',
                'recommendation' => $recommendation,
                'queueState' => $queueState,
            ],
            'reviewerOptions' => Reviewer::query()
                ->with('user:id,name')
                ->where('is_active', true)
                ->get()
                ->map(fn (Reviewer $reviewer): array => [
                    'value' => (string) $reviewer->id,
                    'label' => $reviewer->user?->name ?? 'Reviewer',
                ])
                ->all(),
            'stageOptions' => Submission::query()
                ->join('stages', 'stages.id', '=', 'submissions.current_stage_id')
                ->whereHas('reviewerAssignments')
                ->distinct()
                ->orderBy('stages.name')
                ->get(['stages.id as id', 'stages.name as name'])
                ->map(fn ($stage): array => [
                    'value' => (string) $stage->id,
                    'label' => $stage->name,
                ])
                ->all(),
            'industryOptions' => Submission::query()
                ->join('industries', 'industries.id', '=', 'submissions.industry_id')
                ->whereHas('reviewerAssignments')
                ->distinct()
                ->orderBy('industries.name')
                ->get(['industries.id as id', 'industries.name as name'])
                ->map(fn ($industry): array => [
                    'value' => (string) $industry->id,
                    'label' => $industry->name,
                ])
                ->all(),
            'recommendationOptions' => collect([
                'pass' => 'Pass',
                'reject' => 'Reject',
                'return_for_revision' => 'Return for revision',
                'escalate' => 'Escalate',
            ])->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
            'queueStateOptions' => [
                ['value' => 'unassigned', 'label' => 'Unassigned'],
                ['value' => 'review_in_progress', 'label' => 'Review in progress'],
                ['value' => 'partially_reviewed', 'label' => 'Partially reviewed'],
                ['value' => 'awaiting_decision', 'label' => 'Awaiting decision'],
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
            'applicant:id,full_name,email,phone',
            'organization:id,display_name,contact_email',
            'reviewerAssignments:id,submission_id,reviewer_id,stage_id,status,assigned_at,due_at',
            'reviewerAssignments.reviewer.user:id,name,email',
            'reviewerAssignments.stage:id,name',
            'reviewerAssignments.screeningReview:id,submission_id,reviewer_assignment_id,eligibility_status,recommendation,score_optional,notes,submitted_at',
            'statusHistory.actor:id,name',
        ]);

        return Inertia::render('admin/Screening/Show', [
            'submission' => [
                ...$this->screeningSummary($submission),
                'summary' => $submission->summary,
                'problemStatement' => $submission->problem_statement,
                'solutionDescription' => $submission->solution_description,
                'businessModel' => $submission->business_model,
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
            'reviewerOptions' => Reviewer::query()
                ->with('user:id,name')
                ->where('is_active', true)
                ->get()
                ->map(fn (Reviewer $reviewer): array => [
                    'value' => (string) $reviewer->id,
                    'label' => $reviewer->user?->name ?? 'Reviewer',
                ])
                ->all(),
            'decisionOptions' => app(SubmissionStatusTransitionService::class)->availableScreeningDecisionTransitions($submission),
        ]);
    }

    public function decide(
        TransitionScreeningDecisionRequest $request,
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
            event: 'negadras.screening.decided',
            description: "Applied screening decision {$toStatus->label()} for {$submission->title}.",
            subject: $submission,
            properties: [
                'to_status' => $toStatus->value,
                'reason' => $request->validated('reason'),
            ],
        );

        $submission->loadMissing('applicant.user');

        $submission->applicant?->user?->notify(new SystemMessageNotification(
            title: "Submission {$toStatus->label()}",
            message: match ($toStatus) {
                SubmissionStatus::Shortlisted => "Your submission {$submission->title} has been shortlisted.",
                SubmissionStatus::Rejected => "Your submission {$submission->title} was not selected for the next stage.",
                SubmissionStatus::IncompleteReturned => "Your submission {$submission->title} needs revision before it can continue.",
                default => "Your submission {$submission->title} was updated.",
            },
            actionUrl: route('submissions.show', $submission),
            actionLabel: 'Open submission',
            level: $toStatus === SubmissionStatus::Rejected ? 'warning' : 'info',
        ));

        return to_route('screening-queue.show', $submission)->with('success', 'Screening decision recorded successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function screeningSummary(Submission $submission): array
    {
        $submittedReviews = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->screeningReview?->submitted_at !== null)
            ->values();
        $activeAssignments = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->status->isActive())
            ->values();
        $latestSubmittedReview = $submittedReviews
            ->sortByDesc(fn (ReviewerAssignment $assignment) => $assignment->screeningReview?->submitted_at?->timestamp ?? 0)
            ->first();

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
            'summary' => $submission->summary,
            'latestStatusReason' => $submission->statusHistory->first()?->reason,
            'screeningState' => $this->screeningState($submission),
            'screeningStateLabel' => str($this->screeningState($submission))->replace('_', ' ')->title()->toString(),
            'assignedReviewersCount' => $submission->reviewerAssignments->count(),
            'pendingReviewsCount' => $activeAssignments->count(),
            'submittedReviewsCount' => $submittedReviews->count(),
            'latestRecommendation' => $latestSubmittedReview?->screeningReview?->recommendation?->value,
            'latestRecommendationLabel' => $latestSubmittedReview?->screeningReview?->recommendation?->label(),
            'reviewerAssignments' => $submission->reviewerAssignments
                ->map(fn (ReviewerAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'submissionId' => $assignment->submission_id,
                    'reviewerName' => $assignment->reviewer?->user?->name,
                    'reviewerEmail' => $assignment->reviewer?->user?->email,
                    'stageName' => $assignment->stage?->name,
                    'status' => $assignment->status->value,
                    'statusLabel' => $assignment->status->label(),
                    'statusTone' => $assignment->status->tone(),
                    'dueAt' => $assignment->due_at?->toDateTimeString(),
                    'assignedAt' => $assignment->assigned_at?->toDateTimeString(),
                    'isOverdue' => $assignment->due_at?->isPast() && $assignment->status->isActive(),
                    'recommendationLabel' => $assignment->screeningReview?->recommendation?->label(),
                    'review' => $assignment->screeningReview === null ? null : [
                        'eligibilityStatus' => $assignment->screeningReview->eligibility_status?->value,
                        'recommendation' => $assignment->screeningReview->recommendation?->value,
                        'scoreOptional' => $assignment->screeningReview->score_optional,
                        'notes' => $assignment->screeningReview->notes,
                        'submittedAt' => $assignment->screeningReview->submitted_at?->toDateTimeString(),
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    private function screeningState(Submission $submission): string
    {
        $submittedReviewsCount = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->screeningReview?->submitted_at !== null)
            ->count();
        $activeAssignmentsCount = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => in_array($assignment->status, [
                ReviewerAssignmentStatus::Assigned,
                ReviewerAssignmentStatus::InProgress,
            ], true))
            ->count();

        if ($submission->reviewerAssignments->isEmpty()) {
            return 'unassigned';
        }

        if ($submittedReviewsCount > 0 && $activeAssignmentsCount === 0) {
            return 'awaiting_decision';
        }

        if ($submittedReviewsCount > 0) {
            return 'partially_reviewed';
        }

        return 'review_in_progress';
    }
}
