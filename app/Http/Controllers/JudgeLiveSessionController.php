<?php

namespace App\Http\Controllers;

use App\JudgeCommentType;
use App\Models\CompetitionSession;
use App\Models\PanelSubmissionAssignment;
use App\Models\RubricCriterion;
use App\SessionAppearanceStatus;
use App\Support\ScoreEngine;
use App\Support\SubmissionFileRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class JudgeLiveSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $judge = $request->user()?->judge;
        abort_if($judge === null, 403);

        return Inertia::render('judges/LiveIndex', [
            'sessions' => CompetitionSession::query()
                ->with(['season:id,name', 'stage:id,name', 'panel.members'])
                ->whereHas('panel.members', fn ($query) => $query->where('judge_id', $judge->id))
                ->latest('scheduled_at')
                ->get()
                ->map(fn (CompetitionSession $session): array => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'seasonName' => $session->season?->name,
                    'stageName' => $session->stage?->name,
                    'statusLabel' => $session->status->label(),
                    'statusTone' => $session->status->tone(),
                    'scheduledAt' => $session->scheduled_at?->toDateTimeString(),
                ])
                ->all(),
        ]);
    }

    public function show(Request $request, CompetitionSession $competitionSession, ScoreEngine $scoreEngine): Response
    {
        $judge = $request->user()?->judge;
        abort_if($judge === null, 403);
        abort_unless(
            $competitionSession->panel()
                ->whereHas('members', fn ($query) => $query->where('judge_id', $judge->id))
                ->exists(),
            403,
        );

        $competitionSession->load([
            'season:id,name',
            'stage:id,name',
            'panel.rubric.criteria',
            'snapshot',
            'presenters.submission.applicant:id,full_name,email',
            'presenters.submission.organization:id,display_name',
        ]);

        $currentPresenter = $competitionSession->presenters->firstWhere('appearance_status', SessionAppearanceStatus::Live);
        /** @var Collection<int, RubricCriterion> $rubricCriteria */
        $rubricCriteria = $competitionSession->panel?->rubric?->criteria ?? collect();
        $assignment = $currentPresenter === null
            ? null
            : PanelSubmissionAssignment::query()
                ->with(['scoreEntries', 'judgeComments', 'submission.files'])
                ->where('panel_id', $competitionSession->panel_id)
                ->where('submission_id', $currentPresenter->submission_id)
                ->latest('assigned_at')
                ->first();

        return Inertia::render('judges/LiveShow', [
            'session' => [
                'id' => $competitionSession->id,
                'name' => $competitionSession->name,
                'seasonName' => $competitionSession->season?->name,
                'stageName' => $competitionSession->stage?->name,
                'statusLabel' => $competitionSession->status->label(),
                'statusTone' => $competitionSession->status->tone(),
                'scoresRevealed' => $competitionSession->scores_revealed,
            ],
            'currentPresenter' => $currentPresenter === null || $assignment === null ? null : [
                'assignmentId' => $assignment->id,
                'title' => $currentPresenter->submission?->title,
                'presenterName' => $currentPresenter->submission?->applicant?->full_name,
                'organizationName' => $currentPresenter->submission?->organization?->display_name,
                'progress' => $scoreEngine->judgeProgress($assignment, $judge),
                'isLocked' => $scoreEngine->isLocked($assignment),
                'rubric' => $rubricCriteria->map(fn ($criterion): array => [
                    'id' => $criterion->id,
                    'name' => $criterion->name,
                    'description' => $criterion->description,
                    'maxScore' => (float) $criterion->max_score,
                    'weight' => (float) $criterion->weight,
                    'existingScore' => $assignment->scoreEntries
                        ->where('judge_id', $judge->id)
                        ->firstWhere('rubric_criterion_id', $criterion->id)?->score_value,
                    'existingComment' => $assignment->scoreEntries
                        ->where('judge_id', $judge->id)
                        ->firstWhere('rubric_criterion_id', $criterion->id)?->comment,
                ])->values()->all(),
                'privateComment' => $assignment->judgeComments
                    ->where('judge_id', $judge->id)
                    ->firstWhere('comment_type', JudgeCommentType::Private)?->content,
                'presenterComment' => $assignment->judgeComments
                    ->where('judge_id', $judge->id)
                    ->firstWhere('comment_type', JudgeCommentType::PresenterVisible)?->content,
                'files' => $assignment->submission?->files->map(fn ($file): array => [
                    'id' => $file->id,
                    'label' => SubmissionFileRegistry::definition($file->file_type)['label'] ?? $file->file_type,
                    'originalName' => $file->original_name,
                    'downloadUrl' => route('submission-files.download', $file),
                ])->all() ?? [],
            ],
            'snapshot' => $competitionSession->snapshot?->status_payload,
        ]);
    }
}
