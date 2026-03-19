<?php

namespace App\Http\Controllers;

use App\Models\AwardRecord;
use App\Models\CompetitionSession;
use App\Models\PresenterFeedbackPacket;
use App\Models\RankingSnapshot;
use App\Models\ReviewDecision;
use App\Models\ReviewerAssignment;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(): Response
    {
        $seasonBreakdown = Season::query()
            ->withCount('submissions')
            ->orderByDesc('year')
            ->get()
            ->map(fn (Season $season): array => [
                'id' => $season->id,
                'name' => $season->name,
                'year' => $season->year,
                'submissionsCount' => $season->submissions_count,
            ])
            ->all();

        $stageBreakdown = Stage::query()
            ->withCount('submissions')
            ->with('season:id,name')
            ->orderBy('order_index')
            ->get()
            ->map(fn (Stage $stage): array => [
                'id' => $stage->id,
                'name' => $stage->name,
                'seasonName' => $stage->season?->name,
                'submissionsCount' => $stage->submissions_count,
            ])
            ->all();

        return Inertia::render('reports/Index', [
            'summary' => [
                'totalSubmissions' => Submission::query()->count(),
                'draftSubmissions' => Submission::query()->where('status', SubmissionStatus::Draft)->count(),
                'submittedSubmissions' => Submission::query()->where('status', SubmissionStatus::Submitted)->count(),
                'eligibleSubmissions' => Submission::query()->where('status', SubmissionStatus::Eligible)->count(),
                'shortlistedSubmissions' => Submission::query()->where('status', SubmissionStatus::Shortlisted)->count(),
                'rejectedSubmissions' => Submission::query()->where('status', SubmissionStatus::Rejected)->count(),
                'activeReviewAssignments' => ReviewerAssignment::query()
                    ->whereIn('status', [ReviewerAssignmentStatus::Assigned, ReviewerAssignmentStatus::InProgress])
                    ->count(),
                'submittedReviews' => ReviewerAssignment::query()
                    ->where('status', ReviewerAssignmentStatus::Submitted)
                    ->count(),
                'finalizedSessions' => CompetitionSession::query()->whereNotNull('completed_at')->count(),
                'rankingSnapshots' => RankingSnapshot::query()->count(),
                'awardsGranted' => AwardRecord::query()->count(),
                'feedbackReleased' => PresenterFeedbackPacket::query()->whereNotNull('sent_at_optional')->count(),
            ],
            'seasonBreakdown' => $seasonBreakdown,
            'stageBreakdown' => $stageBreakdown,
            'decisionBreakdown' => ReviewDecision::query()
                ->selectRaw('decision_type, count(*) as total')
                ->groupBy('decision_type')
                ->orderBy('decision_type')
                ->get()
                ->map(fn (ReviewDecision $decision): array => [
                    'decisionType' => $decision->decision_type->value,
                    'decisionLabel' => $decision->decision_type->label(),
                    'total' => (int) $decision->total,
                ])
                ->all(),
        ]);
    }
}
