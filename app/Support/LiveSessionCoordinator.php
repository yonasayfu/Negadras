<?php

namespace App\Support;

use App\CompetitionSessionStatus;
use App\DashboardProjectionStatus;
use App\Models\CompetitionSession;
use App\Models\DashboardProjectionSession;
use App\Models\LiveStatusSnapshot;
use App\Models\PanelSubmissionAssignment;
use App\Models\SessionEvent;
use App\Models\SessionPresenter;
use App\Models\User;
use App\SessionAppearanceStatus;
use App\SessionEventType;

class LiveSessionCoordinator
{
    public function __construct(private readonly LiveStatusSnapshotBuilder $snapshotBuilder) {}

    public function startSession(CompetitionSession $session, ?User $actor = null): void
    {
        $session->update([
            'status' => CompetitionSessionStatus::Live,
            'started_at' => now(),
            'paused_at' => null,
            'broadcasted_at' => $session->broadcasted_at ?? now(),
        ]);

        if ($session->presenters()->where('appearance_status', SessionAppearanceStatus::Live)->doesntExist()) {
            $firstPresenter = $session->presenters()->orderBy('order_index')->first();

            if ($firstPresenter !== null) {
                $this->activatePresenter($session, $firstPresenter, $actor);

                return;
            }
        }

        $this->recordEvent($session, SessionEventType::SessionStarted, [], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function pauseSession(CompetitionSession $session, ?User $actor = null): void
    {
        $session->update([
            'status' => CompetitionSessionStatus::Paused,
            'paused_at' => now(),
        ]);

        $this->recordEvent($session, SessionEventType::SessionPaused, [], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function resumeSession(CompetitionSession $session, ?User $actor = null): void
    {
        $session->update([
            'status' => CompetitionSessionStatus::Live,
            'paused_at' => null,
        ]);

        $this->recordEvent($session, SessionEventType::SessionResumed, [], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function completeSession(CompetitionSession $session, ?User $actor = null): void
    {
        $session->presenters()
            ->where('appearance_status', SessionAppearanceStatus::Live)
            ->update([
                'appearance_status' => SessionAppearanceStatus::Completed,
                'ended_at' => now(),
            ]);

        $session->update([
            'status' => CompetitionSessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->recordEvent($session, SessionEventType::SessionCompleted, [], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function activatePresenter(CompetitionSession $session, SessionPresenter $presenter, ?User $actor = null): void
    {
        $session->presenters()
            ->where('appearance_status', SessionAppearanceStatus::Live)
            ->whereKeyNot($presenter->id)
            ->update([
                'appearance_status' => SessionAppearanceStatus::Completed,
                'ended_at' => now(),
            ]);

        $presenter->update([
            'appearance_status' => SessionAppearanceStatus::Live,
            'started_at' => now(),
        ]);

        PanelSubmissionAssignment::query()
            ->where('panel_id', $session->panel_id)
            ->where('submission_id', $presenter->submission_id)
            ->update(['session_id_optional' => $session->id]);

        $this->recordEvent($session, SessionEventType::PresenterStarted, [
            'session_presenter_id' => $presenter->id,
            'submission_id' => $presenter->submission_id,
        ], $actor);

        $this->refreshSnapshot($session, $actor);
    }

    public function advancePresenter(CompetitionSession $session, ?User $actor = null): void
    {
        $current = $session->presenters()->where('appearance_status', SessionAppearanceStatus::Live)->first();

        if ($current !== null) {
            $current->update([
                'appearance_status' => SessionAppearanceStatus::Completed,
                'ended_at' => now(),
            ]);
        }

        $next = $session->presenters()
            ->where('order_index', '>', $current?->order_index ?? 0)
            ->orderBy('order_index')
            ->first();

        if ($next !== null) {
            $next->update([
                'appearance_status' => SessionAppearanceStatus::Live,
                'started_at' => now(),
            ]);
        }

        $this->recordEvent($session, SessionEventType::PresenterAdvanced, [
            'from_session_presenter_id' => $current?->id,
            'to_session_presenter_id' => $next?->id,
        ], $actor);

        $this->refreshSnapshot($session, $actor);
    }

    /**
     * @param  array<int, array{id:int,order_index:int}>  $rows
     */
    public function reorderQueue(CompetitionSession $session, array $rows, ?User $actor = null): void
    {
        collect($rows)->each(function (array $row) use ($session): void {
            $session->presenters()->whereKey($row['id'])->update(['order_index' => $row['order_index']]);
        });

        $this->recordEvent($session, SessionEventType::QueueReordered, ['rows' => $rows], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function setScoresVisibility(CompetitionSession $session, bool $reveal, ?User $actor = null): void
    {
        $session->update(['scores_revealed' => $reveal]);
        $this->recordEvent($session, $reveal ? SessionEventType::ScoresRevealed : SessionEventType::ScoresHidden, [], $actor);
        $this->refreshSnapshot($session, $actor);
    }

    public function updateProjection(
        CompetitionSession $session,
        string $intent,
        ?string $sourceLabel,
        ?string $notes,
        ?User $actor = null,
    ): DashboardProjectionSession {
        $projection = $session->projectionRequests()->latest('requested_at')->first();

        if ($intent === 'request' || $projection === null) {
            $projection = DashboardProjectionSession::query()->create([
                'competition_session_id' => $session->id,
                'status' => DashboardProjectionStatus::Requested,
                'source_label' => $sourceLabel,
                'requested_by' => $actor?->id,
                'requested_at' => now(),
                'notes' => $notes,
            ]);
            $this->recordEvent($session, SessionEventType::ProjectionRequested, ['projection_id' => $projection->id], $actor);
        } elseif ($intent === 'approve') {
            $projection->update([
                'status' => DashboardProjectionStatus::Active,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
                'started_at' => now(),
                'source_label' => $sourceLabel,
                'notes' => $notes,
            ]);
            $this->recordEvent($session, SessionEventType::ProjectionApproved, ['projection_id' => $projection->id], $actor);
        } else {
            $projection->update([
                'status' => DashboardProjectionStatus::Ended,
                'ended_at' => now(),
                'notes' => $notes,
            ]);
            $this->recordEvent($session, SessionEventType::ProjectionEnded, ['projection_id' => $projection->id], $actor);
        }

        $this->refreshSnapshot($session, $actor);

        return $projection->fresh();
    }

    public function refreshSnapshot(CompetitionSession $session, ?User $actor = null): LiveStatusSnapshot
    {
        $session->loadMissing('presenters');
        $currentPresenter = $session->presenters->firstWhere('appearance_status', SessionAppearanceStatus::Live);

        return LiveStatusSnapshot::query()->updateOrCreate(
            ['competition_session_id' => $session->id],
            [
                'current_session_presenter_id' => $currentPresenter?->id,
                'status_payload' => $this->snapshotBuilder->build($session->fresh()),
                'updated_by' => $actor?->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordEvent(CompetitionSession $session, SessionEventType $eventType, array $payload, ?User $actor = null): void
    {
        SessionEvent::query()->create([
            'competition_session_id' => $session->id,
            'event_type' => $eventType,
            'payload_json' => $payload,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
