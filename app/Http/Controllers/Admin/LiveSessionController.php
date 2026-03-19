<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderSessionPresentersRequest;
use App\Http\Requests\Admin\StoreSessionPresenterRequest;
use App\Http\Requests\Admin\TransitionCompetitionSessionRequest;
use App\Http\Requests\Admin\UpdateProjectionSessionRequest;
use App\Models\CompetitionSession;
use App\Models\SessionPresenter;
use App\Models\Submission;
use App\SessionAppearanceStatus;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\LiveSessionCoordinator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LiveSessionController extends Controller
{
    public function show(CompetitionSession $competitionSession): Response
    {
        $this->authorize('view', $competitionSession);

        $competitionSession->load([
            'season:id,name',
            'stage:id,name',
            'panel.members.judge.user:id,name',
            'presenters.submission.applicant:id,full_name',
            'presenters.submission.organization:id,display_name',
            'projectionRequests.requester:id,name',
            'projectionRequests.approver:id,name',
            'snapshot',
        ]);

        return Inertia::render('admin/LiveSessions/Show', [
            'session' => [
                'id' => $competitionSession->id,
                'name' => $competitionSession->name,
                'seasonName' => $competitionSession->season?->name,
                'stageName' => $competitionSession->stage?->name,
                'status' => $competitionSession->status->value,
                'statusLabel' => $competitionSession->status->label(),
                'statusTone' => $competitionSession->status->tone(),
                'scoresRevealed' => $competitionSession->scores_revealed,
                'queue' => $competitionSession->presenters->map(fn ($presenter): array => [
                    'id' => $presenter->id,
                    'submissionId' => $presenter->submission_id,
                    'title' => $presenter->submission?->title,
                    'presenterName' => $presenter->submission?->applicant?->full_name,
                    'organizationName' => $presenter->submission?->organization?->display_name,
                    'orderIndex' => $presenter->order_index,
                    'appearanceStatus' => $presenter->appearance_status->value,
                    'appearanceStatusLabel' => $presenter->appearance_status->label(),
                ])->values()->all(),
                'snapshot' => $competitionSession->snapshot?->status_payload,
                'projection' => $competitionSession->projectionRequests->first() === null ? null : [
                    'id' => $competitionSession->projectionRequests->first()?->id,
                    'status' => $competitionSession->projectionRequests->first()?->status->value,
                    'statusLabel' => $competitionSession->projectionRequests->first()?->status->label(),
                    'sourceLabel' => $competitionSession->projectionRequests->first()?->source_label,
                    'requesterName' => $competitionSession->projectionRequests->first()?->requester?->name,
                    'approverName' => $competitionSession->projectionRequests->first()?->approver?->name,
                ],
            ],
            'availableSubmissions' => Submission::query()
                ->whereIn('status', [SubmissionStatus::Eligible, SubmissionStatus::Shortlisted])
                ->whereDoesntHave('sessionPresenters', fn ($query) => $query->where('competition_session_id', $competitionSession->id))
                ->with(['applicant:id,full_name'])
                ->orderBy('title')
                ->get()
                ->map(fn ($submission): array => [
                    'value' => $submission->id,
                    'label' => $submission->title.' · '.($submission->applicant?->full_name ?? 'Unknown presenter'),
                ])
                ->all(),
        ]);
    }

    public function storePresenter(
        StoreSessionPresenterRequest $request,
        CompetitionSession $competitionSession,
        LiveSessionCoordinator $coordinator,
    ): RedirectResponse {
        $this->authorize('update', $competitionSession);

        $nextOrder = (int) $competitionSession->presenters()->max('order_index') + 1;

        $competitionSession->presenters()->create([
            'submission_id' => $request->validated('submission_id'),
            'order_index' => $nextOrder,
            'appearance_status' => SessionAppearanceStatus::Queued,
        ]);

        $coordinator->refreshSnapshot($competitionSession, $request->user());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.live-session-presenters.created',
            description: "Added a presenter to live session {$competitionSession->name}.",
            subject: $competitionSession,
            request: $request,
        );

        return to_route('live-sessions.show', $competitionSession)->with('success', 'Presenter added to the session queue.');
    }

    public function reorder(
        ReorderSessionPresentersRequest $request,
        CompetitionSession $competitionSession,
        LiveSessionCoordinator $coordinator,
    ): RedirectResponse {
        $this->authorize('update', $competitionSession);

        $coordinator->reorderQueue($competitionSession, $request->validated('presenters'), $request->user());

        return to_route('live-sessions.show', $competitionSession)->with('success', 'Queue reordered successfully.');
    }

    public function transition(
        TransitionCompetitionSessionRequest $request,
        CompetitionSession $competitionSession,
        LiveSessionCoordinator $coordinator,
    ): RedirectResponse {
        $this->authorize('update', $competitionSession);

        match ($request->validated('intent')) {
            'start' => $coordinator->startSession($competitionSession, $request->user()),
            'pause' => $coordinator->pauseSession($competitionSession, $request->user()),
            'resume' => $coordinator->resumeSession($competitionSession, $request->user()),
            'complete' => $coordinator->completeSession($competitionSession, $request->user()),
            'activate_presenter' => $coordinator->activatePresenter(
                $competitionSession,
                SessionPresenter::query()->findOrFail($request->validated('session_presenter_id')),
                $request->user(),
            ),
            'advance_presenter' => $coordinator->advancePresenter($competitionSession, $request->user()),
            'reveal_scores' => $coordinator->setScoresVisibility($competitionSession, true, $request->user()),
            'hide_scores' => $coordinator->setScoresVisibility($competitionSession, false, $request->user()),
        };

        return to_route('live-sessions.show', $competitionSession)->with('success', 'Live session updated successfully.');
    }

    public function updateProjection(
        UpdateProjectionSessionRequest $request,
        CompetitionSession $competitionSession,
        LiveSessionCoordinator $coordinator,
    ): RedirectResponse {
        $this->authorize('update', $competitionSession);

        $coordinator->updateProjection(
            session: $competitionSession,
            intent: $request->validated('intent'),
            sourceLabel: $request->validated('source_label'),
            notes: $request->validated('notes'),
            actor: $request->user(),
        );

        return to_route('live-sessions.show', $competitionSession)->with('success', 'Projection state updated successfully.');
    }
}
