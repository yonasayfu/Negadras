<?php

namespace App\Support;

use App\Models\CompetitionSession;
use App\Models\PanelSubmissionAssignment;
use App\Models\SessionPresenter;
use App\SessionAppearanceStatus;

class LiveStatusSnapshotBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(CompetitionSession $session): array
    {
        $session->loadMissing([
            'season:id,name,year',
            'stage:id,name',
            'panel.members.judge.user:id,name',
            'presenters.submission.applicant:id,full_name',
            'presenters.submission.organization:id,display_name',
        ]);

        $currentPresenter = $session->presenters->firstWhere('appearance_status', SessionAppearanceStatus::Live);
        $nextPresenter = $session->presenters
            ->where('order_index', '>', $currentPresenter?->order_index ?? 0)
            ->sortBy('order_index')
            ->first();

        return [
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'type' => $session->session_type->value,
                'typeLabel' => $session->session_type->label(),
                'status' => $session->status->value,
                'statusLabel' => $session->status->label(),
                'statusTone' => $session->status->tone(),
                'location' => $session->location,
                'seasonName' => $session->season?->name,
                'stageName' => $session->stage?->name,
                'panelName' => $session->panel?->name,
                'scheduledAt' => $session->scheduled_at?->toDateTimeString(),
                'scoresRevealed' => $session->scores_revealed,
            ],
            'currentPresenter' => $currentPresenter === null ? null : $this->presenterPayload($session, $currentPresenter),
            'nextPresenter' => $nextPresenter === null ? null : $this->simplePresenterPayload($nextPresenter),
            'judgeCompletion' => $this->judgeCompletion($session, $currentPresenter),
            'queue' => $session->presenters
                ->map(fn (SessionPresenter $presenter): array => $this->simplePresenterPayload($presenter))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presenterPayload(CompetitionSession $session, SessionPresenter $presenter): array
    {
        $assignment = $this->assignment($session, $presenter);
        $scoreEngine = app(ScoreEngine::class);

        return [
            ...$this->simplePresenterPayload($presenter),
            'summary' => $presenter->submission?->summary,
            'problemStatement' => $presenter->submission?->problem_statement,
            'solutionDescription' => $presenter->submission?->solution_description,
            'aggregateScore' => $assignment === null ? null : $scoreEngine->aggregateTotal($assignment),
            'isScoreVisible' => $session->scores_revealed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function simplePresenterPayload(SessionPresenter $presenter): array
    {
        return [
            'id' => $presenter->id,
            'submissionId' => $presenter->submission_id,
            'title' => $presenter->submission?->title,
            'presenterName' => $presenter->submission?->applicant?->full_name,
            'organizationName' => $presenter->submission?->organization?->display_name,
            'appearanceStatus' => $presenter->appearance_status->value,
            'appearanceStatusLabel' => $presenter->appearance_status->label(),
            'orderIndex' => $presenter->order_index,
            'startedAt' => $presenter->started_at?->toDateTimeString(),
            'endedAt' => $presenter->ended_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function judgeCompletion(CompetitionSession $session, ?SessionPresenter $presenter): array
    {
        if ($presenter === null || $session->panel === null) {
            return ['totalJudges' => 0, 'completedJudges' => 0];
        }

        $assignment = $this->assignment($session, $presenter);

        if ($assignment === null) {
            return [
                'totalJudges' => (int) $session->panel->members->count(),
                'completedJudges' => 0,
            ];
        }

        $scoreEngine = app(ScoreEngine::class);
        $completedJudges = $session->panel->members
            ->filter(fn ($member): bool => $member->judge !== null)
            ->filter(fn ($member): bool => (bool) $scoreEngine->judgeProgress($assignment, $member->judge)['isComplete'])
            ->count();

        return [
            'totalJudges' => (int) $session->panel->members->count(),
            'completedJudges' => (int) $completedJudges,
        ];
    }

    private function assignment(CompetitionSession $session, SessionPresenter $presenter): ?PanelSubmissionAssignment
    {
        return PanelSubmissionAssignment::query()
            ->where('submission_id', $presenter->submission_id)
            ->where('panel_id', $session->panel_id)
            ->where(function ($query) use ($session): void {
                $query->where('session_id_optional', $session->id)
                    ->orWhereNull('session_id_optional');
            })
            ->with(['scoreEntries', 'panel.rubric.criteria', 'panel.members.judge'])
            ->latest('assigned_at')
            ->first();
    }
}
