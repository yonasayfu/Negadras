<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePanelRequest;
use App\Http\Requests\Admin\UpdatePanelRequest;
use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelMember;
use App\Models\Rubric;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\PanelStatus;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\ScoreEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PanelManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Panel::class);

        $search = $request->string('search')->trim()->toString();
        $seasonId = $request->integer('season_id');
        $status = $request->string('status')->trim()->toString();

        return Inertia::render('admin/Panels/Index', [
            'panels' => Panel::query()
                ->with(['season:id,name', 'stage:id,name', 'rubric:id,name'])
                ->withCount(['members', 'submissionAssignments'])
                ->when($search !== '', fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
                ->when($seasonId > 0, fn ($query) => $query->where('season_id', $seasonId))
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Panel $panel): array => $this->panelSummary($panel)),
            'filters' => [
                'search' => $search,
                'seasonId' => $seasonId > 0 ? (string) $seasonId : '',
                'status' => $status,
            ],
            'seasonOptions' => $this->seasonOptions(),
            'statusOptions' => collect(PanelStatus::cases())->map(fn (PanelStatus $statusCase): array => [
                'value' => $statusCase->value,
                'label' => $statusCase->label(),
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Panel::class);

        return Inertia::render('admin/Panels/Create', $this->panelFormProps());
    }

    public function store(StorePanelRequest $request): RedirectResponse
    {
        $this->authorize('create', Panel::class);

        $validated = $request->validated();

        $panel = Panel::query()->create([
            'season_id' => $validated['season_id'],
            'stage_id' => $validated['stage_id'],
            'rubric_id' => $validated['rubric_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->syncMembers($panel, $validated['members'] ?? []);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.panels.created',
            description: "Created panel {$panel->name}.",
            subject: $panel,
            request: $request,
        );

        return to_route('panels.show', $panel)->with('success', 'Panel created successfully.');
    }

    public function show(Panel $panel, ScoreEngine $scoreEngine): Response
    {
        $this->authorize('view', $panel);

        $panel->load([
            'season:id,name',
            'stage:id,name',
            'rubric.criteria',
            'members.judge.user:id,name,email',
            'submissionAssignments.submission.season:id,name',
            'submissionAssignments.submission.applicant:id,full_name,email',
            'submissionAssignments.submission.organization:id,display_name',
            'submissionAssignments.scoreEntries.judge.user:id,name',
            'submissionAssignments.currentLock.locker:id,name',
            'submissionAssignments.visibilityEvents.actor:id,name',
        ]);

        return Inertia::render('admin/Panels/Show', [
            'panel' => $this->panelDetail($panel, $scoreEngine),
            'availableSubmissionOptions' => Submission::query()
                ->with(['applicant:id,full_name', 'organization:id,display_name'])
                ->where('current_stage_id', $panel->stage_id)
                ->whereIn('status', [SubmissionStatus::Eligible, SubmissionStatus::Shortlisted])
                ->whereDoesntHave('panelAssignments', function ($query) use ($panel): void {
                    $query->where('panel_id', $panel->id);
                })
                ->orderBy('title')
                ->get(['id', 'title', 'applicant_id', 'organization_id'])
                ->map(fn (Submission $submission): array => [
                    'value' => (string) $submission->id,
                    'label' => trim("{$submission->title} - ".($submission->organization?->display_name ?? $submission->applicant?->full_name ?? 'Unknown')),
                ])
                ->all(),
        ]);
    }

    public function edit(Panel $panel): Response
    {
        $this->authorize('view', $panel);

        $panel->load(['members.judge.user:id,name,email']);

        return Inertia::render('admin/Panels/Edit', [
            ...$this->panelFormProps(),
            'panel' => $this->panelEditPayload($panel),
        ]);
    }

    public function update(UpdatePanelRequest $request, Panel $panel): RedirectResponse
    {
        $this->authorize('update', $panel);

        $validated = $request->validated();

        $panel->update([
            'season_id' => $validated['season_id'],
            'stage_id' => $validated['stage_id'],
            'rubric_id' => $validated['rubric_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->syncMembers($panel, $validated['members'] ?? []);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.panels.updated',
            description: "Updated panel {$panel->name}.",
            subject: $panel,
            request: $request,
        );

        return to_route('panels.edit', $panel)->with('success', 'Panel updated successfully.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $membersPayload
     */
    private function syncMembers(Panel $panel, array $membersPayload): void
    {
        $panel->loadMissing('members');

        $existingIds = $panel->members->pluck('id');
        $incomingIds = collect($membersPayload)->pluck('id')->filter()->map(fn ($id) => (int) $id);

        PanelMember::query()
            ->where('panel_id', $panel->id)
            ->whereIn('id', $existingIds->diff($incomingIds))
            ->delete();

        foreach ($membersPayload as $index => $memberPayload) {
            PanelMember::query()->updateOrCreate(
                [
                    'id' => $memberPayload['id'] ?? null,
                    'panel_id' => $panel->id,
                ],
                [
                    'judge_id' => $memberPayload['judge_id'],
                    'role_in_panel' => $memberPayload['role_in_panel'],
                    'display_order' => $memberPayload['display_order'] ?? ($index + 1),
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function panelFormProps(): array
    {
        return [
            'seasonOptions' => $this->seasonOptions(),
            'stageOptions' => Stage::query()
                ->with('season:id,name')
                ->orderBy('order_index')
                ->get(['id', 'season_id', 'name'])
                ->map(fn (Stage $stage): array => [
                    'value' => (string) $stage->id,
                    'label' => "{$stage->name} ({$stage->season?->name})",
                    'seasonId' => (string) $stage->season_id,
                ])
                ->all(),
            'rubricOptions' => Rubric::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Rubric $rubric): array => [
                    'value' => (string) $rubric->id,
                    'label' => $rubric->name,
                ])
                ->all(),
            'judgeOptions' => Judge::query()
                ->with('user:id,name,email')
                ->where('is_active', true)
                ->orderBy('specialization')
                ->get()
                ->map(fn (Judge $judge): array => [
                    'value' => (string) $judge->id,
                    'label' => trim(($judge->user?->name ?? 'Unknown judge').' - '.($judge->specialization ?? 'No specialization')),
                ])
                ->all(),
            'statusOptions' => collect(PanelStatus::cases())->map(fn (PanelStatus $statusCase): array => [
                'value' => $statusCase->value,
                'label' => $statusCase->label(),
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function seasonOptions(): array
    {
        return Season::query()
            ->orderByDesc('year')
            ->get(['id', 'name'])
            ->map(fn (Season $season): array => [
                'value' => (string) $season->id,
                'label' => $season->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function panelSummary(Panel $panel): array
    {
        return [
            'id' => $panel->id,
            'name' => $panel->name,
            'seasonName' => $panel->season?->name,
            'stageName' => $panel->stage?->name,
            'rubricName' => $panel->rubric?->name,
            'status' => $panel->status->value,
            'statusLabel' => $panel->status->label(),
            'statusTone' => $panel->status->tone(),
            'membersCount' => $panel->members_count ?? $panel->members()->count(),
            'submissionAssignmentsCount' => $panel->submission_assignments_count ?? $panel->submissionAssignments()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panelEditPayload(Panel $panel): array
    {
        return [
            'id' => $panel->id,
            'seasonId' => (string) $panel->season_id,
            'stageId' => (string) $panel->stage_id,
            'rubricId' => (string) $panel->rubric_id,
            'name' => $panel->name,
            'description' => $panel->description,
            'status' => $panel->status->value,
            'members' => $panel->members
                ->sortBy('display_order')
                ->values()
                ->map(fn (PanelMember $member): array => [
                    'id' => $member->id,
                    'judgeId' => (string) $member->judge_id,
                    'judgeName' => $member->judge?->user?->name,
                    'roleInPanel' => $member->role_in_panel->value,
                    'displayOrder' => $member->display_order,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panelDetail(Panel $panel, ScoreEngine $scoreEngine): array
    {
        return [
            ...$this->panelSummary($panel),
            'description' => $panel->description,
            'criteria' => $panel->rubric?->criteria
                ->map(fn ($criterion): array => [
                    'id' => $criterion->id,
                    'name' => $criterion->name,
                    'weight' => (float) $criterion->weight,
                    'maxScore' => (float) $criterion->max_score,
                ])
                ->all() ?? [],
            'members' => $panel->members
                ->sortBy('display_order')
                ->values()
                ->map(fn (PanelMember $member): array => [
                    'id' => $member->id,
                    'judgeId' => $member->judge_id,
                    'name' => $member->judge?->user?->name,
                    'email' => $member->judge?->user?->email,
                    'specialization' => $member->judge?->specialization,
                    'roleLabel' => $member->role_in_panel->label(),
                    'displayOrder' => $member->display_order,
                ])
                ->all(),
            'assignments' => $panel->submissionAssignments
                ->map(function ($assignment) use ($scoreEngine): array {
                    return [
                        'id' => $assignment->id,
                        'submissionId' => $assignment->submission_id,
                        'title' => $assignment->submission?->title,
                        'applicantName' => $assignment->submission?->applicant?->full_name,
                        'organizationName' => $assignment->submission?->organization?->display_name,
                        'status' => $assignment->status->value,
                        'statusLabel' => $assignment->status->label(),
                        'statusTone' => $assignment->status->tone(),
                        'assignedAt' => $assignment->assigned_at?->toDateTimeString(),
                        'aggregateScore' => $scoreEngine->aggregateTotal($assignment),
                        'isLocked' => $scoreEngine->isLocked($assignment),
                        'latestVisibilityAction' => $assignment->visibilityEvents->first()?->action?->label(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
