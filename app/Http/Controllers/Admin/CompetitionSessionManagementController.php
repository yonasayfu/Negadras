<?php

namespace App\Http\Controllers\Admin;

use App\CompetitionSessionStatus;
use App\CompetitionSessionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionSessionRequest;
use App\Http\Requests\Admin\UpdateCompetitionSessionRequest;
use App\Models\CompetitionSession;
use App\Models\Media;
use App\Models\Panel;
use App\Models\Season;
use App\Models\Stage;
use App\Support\ActivityLogger;
use App\Support\LiveSessionCoordinator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionSessionManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CompetitionSession::class);

        $sessions = CompetitionSession::query()
            ->with(['season:id,name', 'stage:id,name', 'panel:id,name', 'presenters'])
            ->when($request->string('status')->toString() !== '', function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest('scheduled_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/CompetitionSessions/Index', [
            'filters' => $request->only(['status']),
            'sessions' => $sessions->through(fn (CompetitionSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'seasonName' => $session->season?->name,
                'stageName' => $session->stage?->name,
                'panelName' => $session->panel?->name,
                'typeLabel' => $session->session_type->label(),
                'statusLabel' => $session->status->label(),
                'statusTone' => $session->status->tone(),
                'scheduledAt' => $session->scheduled_at?->toDateTimeString(),
                'location' => $session->location,
                'presentersCount' => $session->presenters->count(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CompetitionSession::class);

        return Inertia::render('admin/CompetitionSessions/Create', $this->formPayload());
    }

    public function store(StoreCompetitionSessionRequest $request, LiveSessionCoordinator $coordinator): RedirectResponse
    {
        $this->authorize('create', CompetitionSession::class);

        $session = CompetitionSession::query()->create($request->safe()->except(['media_ids']));
        $this->syncMedia($session, $request->validated('media_ids', []));
        $coordinator->refreshSnapshot($session, $request->user());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.competition-sessions.created',
            description: "Created live session {$session->name}.",
            subject: $session,
            request: $request,
        );

        return to_route('competition-sessions.edit', $session)->with('success', 'Session created successfully.');
    }

    public function show(CompetitionSession $competitionSession): Response
    {
        $this->authorize('view', $competitionSession);

        $competitionSession->load([
            'season:id,name',
            'stage:id,name',
            'panel:id,name',
            'presenters.submission.applicant:id,full_name',
            'presenters.submission.organization:id,display_name',
            'events.actor:id,name',
            'projectionRequests.requester:id,name',
            'projectionRequests.approver:id,name',
            'snapshot',
        ]);

        return Inertia::render('admin/CompetitionSessions/Show', [
            'session' => $this->detailPayload($competitionSession),
            'availableSubmissions' => $this->availableSubmissions($competitionSession),
        ]);
    }

    public function edit(CompetitionSession $competitionSession): Response
    {
        $this->authorize('update', $competitionSession);

        $competitionSession->load(['media']);

        return Inertia::render('admin/CompetitionSessions/Edit', [
            ...$this->formPayload(),
            'session' => [
                'id' => $competitionSession->id,
                'seasonId' => $competitionSession->season_id,
                'stageId' => $competitionSession->stage_id,
                'panelId' => $competitionSession->panel_id,
                'name' => $competitionSession->name,
                'sessionType' => $competitionSession->session_type->value,
                'scheduledAt' => $competitionSession->scheduled_at?->format('Y-m-d\TH:i'),
                'location' => $competitionSession->location,
                'status' => $competitionSession->status->value,
                'etvVideoUrlOptional' => $competitionSession->etv_video_url_optional,
                'mediaIds' => $competitionSession->media->pluck('media_id')->all(),
            ],
        ]);
    }

    public function update(
        UpdateCompetitionSessionRequest $request,
        CompetitionSession $competitionSession,
        LiveSessionCoordinator $coordinator,
    ): RedirectResponse {
        $this->authorize('update', $competitionSession);

        $competitionSession->update($request->safe()->except(['media_ids']));
        $this->syncMedia($competitionSession, $request->validated('media_ids', []));
        $coordinator->refreshSnapshot($competitionSession, $request->user());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.competition-sessions.updated',
            description: "Updated live session {$competitionSession->name}.",
            subject: $competitionSession,
            request: $request,
        );

        return to_route('competition-sessions.edit', $competitionSession)->with('success', 'Session updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(): array
    {
        return [
            'seasonOptions' => Season::query()->orderByDesc('year')->get(['id', 'name'])->map(fn ($season): array => [
                'value' => $season->id,
                'label' => $season->name,
            ])->all(),
            'stageOptions' => Stage::query()->with('season:id,name')->orderBy('order_index')->get(['id', 'season_id', 'name'])->map(fn ($stage): array => [
                'value' => $stage->id,
                'label' => "{$stage->season?->name} · {$stage->name}",
            ])->all(),
            'panelOptions' => Panel::query()->with(['season:id,name', 'stage:id,name'])->orderBy('name')->get(['id', 'season_id', 'stage_id', 'name'])->map(fn ($panel): array => [
                'value' => $panel->id,
                'label' => "{$panel->name} · {$panel->season?->name} / {$panel->stage?->name}",
            ])->all(),
            'mediaOptions' => Media::query()->latest()->limit(50)->get(['id', 'file_name'])->map(fn ($media): array => [
                'value' => $media->id,
                'label' => $media->file_name,
            ])->all(),
            'sessionTypeOptions' => collect(CompetitionSessionType::cases())->map(fn ($type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->all(),
            'sessionStatusOptions' => collect(CompetitionSessionStatus::cases())->map(fn ($status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(CompetitionSession $session): array
    {
        return [
            'id' => $session->id,
            'name' => $session->name,
            'seasonName' => $session->season?->name,
            'stageName' => $session->stage?->name,
            'panelName' => $session->panel?->name,
            'typeLabel' => $session->session_type->label(),
            'statusLabel' => $session->status->label(),
            'statusTone' => $session->status->tone(),
            'scheduledAt' => $session->scheduled_at?->toDateTimeString(),
            'location' => $session->location,
            'scoresRevealed' => $session->scores_revealed,
            'presenters' => $session->presenters->map(fn ($presenter): array => [
                'id' => $presenter->id,
                'title' => $presenter->submission?->title,
                'presenterName' => $presenter->submission?->applicant?->full_name,
                'organizationName' => $presenter->submission?->organization?->display_name,
                'orderIndex' => $presenter->order_index,
                'appearanceStatus' => $presenter->appearance_status->value,
                'appearanceStatusLabel' => $presenter->appearance_status->label(),
            ])->values()->all(),
            'events' => $session->events->map(fn ($event): array => [
                'id' => $event->id,
                'eventLabel' => $event->event_type->label(),
                'actorName' => $event->actor?->name,
                'createdAt' => $event->created_at?->toDateTimeString(),
            ])->values()->all(),
            'snapshotUpdatedAt' => $session->snapshot?->updated_at?->toDateTimeString(),
            'projection' => $session->projectionRequests->first() === null ? null : [
                'statusLabel' => $session->projectionRequests->first()?->status->label(),
                'sourceLabel' => $session->projectionRequests->first()?->source_label,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function availableSubmissions(CompetitionSession $session): array
    {
        if ($session->panel_id === null || $session->panel === null) {
            return [];
        }

        $queuedIds = $session->presenters->pluck('submission_id');

        return $session->panel
            ->submissionAssignments()
            ->with(['submission.applicant:id,full_name', 'submission.organization:id,display_name'])
            ->whereNotIn('submission_id', $queuedIds)
            ->get()
            ->map(fn ($assignment): array => [
                'value' => $assignment->submission_id,
                'label' => $assignment->submission?->title.' · '.($assignment->submission?->applicant?->full_name ?? 'Unknown presenter'),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $mediaIds
     */
    private function syncMedia(CompetitionSession $session, array $mediaIds): void
    {
        $session->media()->delete();

        collect($mediaIds)
            ->values()
            ->each(function (int $mediaId, int $index) use ($session): void {
                $session->media()->create([
                    'media_id' => $mediaId,
                    'usage_type' => 'supporting',
                    'display_order' => $index + 1,
                ]);
            });
    }
}
