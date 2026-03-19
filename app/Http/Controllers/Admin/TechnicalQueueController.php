<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use App\TechnicalReviewRecommendation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicalQueueController extends Controller
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

        return Inertia::render('admin/Technical/Index', [
            'submissions' => Submission::query()
                ->with([
                    'season:id,name',
                    'currentStage:id,name',
                    'industry:id,name',
                    'applicant:id,full_name,email',
                    'organization:id,display_name',
                    'reviewerAssignments:id,submission_id,reviewer_id,stage_id,assignment_type,status,assigned_at,due_at',
                    'reviewerAssignments.reviewer.user:id,name',
                    'reviewerAssignments.technicalReview:id,reviewer_assignment_id,recommendation,submitted_at,innovation_score_optional,feasibility_score_optional,execution_score_optional,market_score_optional',
                    'screeningReviews:id,submission_id,reviewer_assignment_id,recommendation,submitted_at',
                ])
                ->where('status', SubmissionStatus::Shortlisted)
                ->when($stageId > 0, fn ($query) => $query->where('current_stage_id', $stageId))
                ->when($industryId > 0, fn ($query) => $query->where('industry_id', $industryId))
                ->when($reviewerId > 0, fn ($query) => $query->whereHas('reviewerAssignments', fn ($assignmentQuery) => $assignmentQuery
                    ->where('assignment_type', ReviewAssignmentType::Technical)
                    ->where('reviewer_id', $reviewerId)))
                ->when($recommendation !== '', fn ($query) => $query->whereHas('technicalReviews', fn ($reviewQuery) => $reviewQuery
                    ->where('recommendation', $recommendation)
                    ->whereNotNull('submitted_at')))
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
                ->filter(fn (Submission $submission): bool => $queueState === '' || $this->technicalState($submission) === $queueState)
                ->values()
                ->map(fn (Submission $submission): array => $this->technicalSummary($submission))
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
            'recommendationOptions' => collect(TechnicalReviewRecommendation::cases())->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'queueStateOptions' => [
                ['value' => 'unassigned', 'label' => 'Unassigned'],
                ['value' => 'review_in_progress', 'label' => 'Review in progress'],
                ['value' => 'partially_reviewed', 'label' => 'Partially reviewed'],
                ['value' => 'fully_reviewed', 'label' => 'Fully reviewed'],
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
            'reviewerAssignments:id,submission_id,reviewer_id,stage_id,assignment_type,status,assigned_at,due_at',
            'reviewerAssignments.reviewer.user:id,name,email',
            'reviewerAssignments.stage:id,name',
            'reviewerAssignments.screeningReview:id,submission_id,reviewer_assignment_id,eligibility_status,recommendation,score_optional,notes,submitted_at',
            'reviewerAssignments.technicalReview:id,submission_id,reviewer_assignment_id,reviewer_id,stage_id,innovation_score_optional,feasibility_score_optional,execution_score_optional,market_score_optional,strengths,weaknesses,risk_note,recommendation,submitted_at',
        ]);

        return Inertia::render('admin/Technical/Show', [
            'submission' => [
                ...$this->technicalSummary($submission),
                'summary' => $submission->summary,
                'problemStatement' => $submission->problem_statement,
                'solutionDescription' => $submission->solution_description,
                'businessModel' => $submission->business_model,
                'technicalAssignments' => $submission->reviewerAssignments
                    ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->isTechnical())
                    ->values()
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
                        'review' => $assignment->technicalReview === null ? null : [
                            'innovationScoreOptional' => $assignment->technicalReview->innovation_score_optional,
                            'feasibilityScoreOptional' => $assignment->technicalReview->feasibility_score_optional,
                            'executionScoreOptional' => $assignment->technicalReview->execution_score_optional,
                            'marketScoreOptional' => $assignment->technicalReview->market_score_optional,
                            'strengths' => $assignment->technicalReview->strengths,
                            'weaknesses' => $assignment->technicalReview->weaknesses,
                            'riskNote' => $assignment->technicalReview->risk_note,
                            'recommendation' => $assignment->technicalReview->recommendation?->value,
                            'recommendationLabel' => $assignment->technicalReview->recommendation?->label(),
                            'submittedAt' => $assignment->technicalReview->submitted_at?->toDateTimeString(),
                        ],
                    ])
                    ->all(),
                'screeningReviews' => $submission->reviewerAssignments
                    ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->isScreening() && $assignment->screeningReview?->submitted_at !== null)
                    ->values()
                    ->map(fn (ReviewerAssignment $assignment): array => [
                        'reviewerName' => $assignment->reviewer?->user?->name,
                        'recommendation' => $assignment->screeningReview?->recommendation?->value,
                        'recommendationLabel' => $assignment->screeningReview?->recommendation?->label(),
                        'eligibilityStatus' => $assignment->screeningReview?->eligibility_status?->label(),
                        'scoreOptional' => $assignment->screeningReview?->score_optional,
                        'notes' => $assignment->screeningReview?->notes,
                        'submittedAt' => $assignment->screeningReview?->submitted_at?->toDateTimeString(),
                    ])
                    ->all(),
            ],
            'reviewerOptions' => Reviewer::query()
                ->with('user:id,name,email')
                ->withCount(['assignments as activeAssignmentsCount' => function ($query): void {
                    $query->where('assignment_type', ReviewAssignmentType::Technical)
                        ->whereIn('status', [
                            ReviewerAssignmentStatus::Assigned,
                            ReviewerAssignmentStatus::InProgress,
                        ]);
                }])
                ->where('is_active', true)
                ->get()
                ->map(fn (Reviewer $reviewer): array => [
                    'value' => (string) $reviewer->id,
                    'label' => sprintf('%s (%s active technical)', $reviewer->user?->name ?? 'Reviewer', $reviewer->activeAssignmentsCount),
                ])
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function technicalSummary(Submission $submission): array
    {
        $technicalAssignments = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->isTechnical())
            ->values();
        $technicalReviews = $technicalAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->technicalReview?->submitted_at !== null)
            ->values();
        $scores = collect([
            $technicalReviews->avg(fn (ReviewerAssignment $assignment) => $assignment->technicalReview?->innovation_score_optional),
            $technicalReviews->avg(fn (ReviewerAssignment $assignment) => $assignment->technicalReview?->feasibility_score_optional),
            $technicalReviews->avg(fn (ReviewerAssignment $assignment) => $assignment->technicalReview?->execution_score_optional),
            $technicalReviews->avg(fn (ReviewerAssignment $assignment) => $assignment->technicalReview?->market_score_optional),
        ])->filter(fn ($value) => $value !== null);

        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'seasonName' => $submission->season?->name,
            'stageName' => $submission->currentStage?->name,
            'industryName' => $submission->industry?->name,
            'organizationName' => $submission->organization?->display_name,
            'applicantName' => $submission->applicant?->full_name,
            'status' => $submission->status->value,
            'statusLabel' => $submission->status->label(),
            'statusTone' => $submission->status->tone(),
            'technicalState' => $this->technicalState($submission),
            'technicalStateLabel' => str($this->technicalState($submission))->replace('_', ' ')->title()->toString(),
            'assignedTechnicalReviewersCount' => $technicalAssignments->count(),
            'submittedTechnicalReviewsCount' => $technicalReviews->count(),
            'screeningReviewsCount' => $submission->screeningReviews->whereNotNull('submitted_at')->count(),
            'latestTechnicalRecommendationLabel' => $technicalReviews
                ->sortByDesc(fn (ReviewerAssignment $assignment) => $assignment->technicalReview?->submitted_at?->timestamp ?? 0)
                ->first()?->technicalReview?->recommendation?->label(),
            'averageTechnicalScore' => $scores->isEmpty() ? null : round($scores->avg(), 1),
        ];
    }

    private function technicalState(Submission $submission): string
    {
        $technicalAssignments = $submission->reviewerAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->isTechnical())
            ->values();

        if ($technicalAssignments->isEmpty()) {
            return 'unassigned';
        }

        $submittedCount = $technicalAssignments
            ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->technicalReview?->submitted_at !== null)
            ->count();

        if ($submittedCount === 0) {
            return 'review_in_progress';
        }

        if ($submittedCount < $technicalAssignments->count()) {
            return 'partially_reviewed';
        }

        return 'fully_reviewed';
    }
}
