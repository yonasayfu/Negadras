<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStageRequest;
use App\Http\Requests\Admin\UpdateStageRequest;
use App\Models\Season;
use App\Models\Stage;
use App\StageStatus;
use App\StageType;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StageManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Stage::class);

        $search = $request->string('search')->trim()->toString();
        $seasonId = $request->integer('season_id');
        $status = $request->string('status')->toString();

        $stages = Stage::query()
            ->with('season:id,name,year')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($stageQuery) use ($search): void {
                    $stageQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->when($seasonId > 0, fn ($query) => $query->where('season_id', $seasonId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('season_id')
            ->orderBy('order_index')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Stage $stage): array => $this->stageSummary($stage));

        return Inertia::render('admin/Stages/Index', [
            'stages' => $stages,
            'filters' => [
                'search' => $search,
                'seasonId' => $seasonId > 0 ? (string) $seasonId : '',
                'status' => $status,
            ],
            'seasonOptions' => $this->seasonOptions(),
            'statusOptions' => $this->stageStatusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Stage::class);

        return Inertia::render('admin/Stages/Create', [
            'seasonOptions' => $this->seasonOptions(),
            'statusOptions' => $this->stageStatusOptions(),
            'typeOptions' => $this->stageTypeOptions(),
        ]);
    }

    public function store(StoreStageRequest $request): RedirectResponse
    {
        $this->authorize('create', Stage::class);

        $stage = Stage::query()->create($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.stages.created',
            description: "Created stage {$stage->name}.",
            subject: $stage,
            properties: [
                'season_id' => $stage->season_id,
                'status' => $stage->status->value,
            ],
            request: $request,
        );

        return to_route('stages.edit', $stage)->with('success', 'Stage created successfully.');
    }

    public function edit(Stage $stage): Response
    {
        $this->authorize('view', $stage);

        $stage->load('season:id,name,year');

        return Inertia::render('admin/Stages/Edit', [
            'stage' => $this->stageSummary($stage),
            'seasonOptions' => $this->seasonOptions(),
            'statusOptions' => $this->stageStatusOptions(),
            'typeOptions' => $this->stageTypeOptions(),
        ]);
    }

    public function update(UpdateStageRequest $request, Stage $stage): RedirectResponse
    {
        $this->authorize('update', $stage);

        $stage->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.stages.updated',
            description: "Updated stage {$stage->name}.",
            subject: $stage,
            properties: [
                'season_id' => $stage->season_id,
                'status' => $stage->status->value,
            ],
            request: $request,
        );

        return to_route('stages.edit', $stage)->with('success', 'Stage updated successfully.');
    }

    public function open(Request $request, Stage $stage): RedirectResponse
    {
        $this->authorize('update', $stage);

        $stage->update(['status' => StageStatus::Open]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.stages.opened',
            description: "Opened stage {$stage->name}.",
            subject: $stage,
            request: $request,
        );

        return to_route('stages.index')->with('success', 'Stage opened successfully.');
    }

    public function close(Request $request, Stage $stage): RedirectResponse
    {
        $this->authorize('update', $stage);

        $stage->update(['status' => StageStatus::Closed]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.stages.closed',
            description: "Closed stage {$stage->name}.",
            subject: $stage,
            request: $request,
        );

        return to_route('stages.index')->with('success', 'Stage closed successfully.');
    }

    public function destroy(Request $request, Stage $stage): RedirectResponse
    {
        $this->authorize('delete', $stage);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.stages.deleted',
            description: "Deleted stage {$stage->name}.",
            subject: $stage,
            request: $request,
        );

        $stage->delete();

        return to_route('stages.index')->with('success', 'Stage deleted successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function seasonOptions(): array
    {
        return Season::query()
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (Season $season): array => [
                'value' => (string) $season->id,
                'label' => "{$season->name} ({$season->year})",
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function stageStatusOptions(): array
    {
        return collect(StageStatus::cases())
            ->map(fn (StageStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function stageTypeOptions(): array
    {
        return collect(StageType::cases())
            ->map(fn (StageType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function stageSummary(Stage $stage): array
    {
        return [
            'id' => $stage->id,
            'seasonId' => (string) $stage->season_id,
            'seasonName' => $stage->season?->name,
            'name' => $stage->name,
            'code' => $stage->code,
            'type' => $stage->type->value,
            'typeLabel' => $stage->type->label(),
            'orderIndex' => $stage->order_index,
            'startsAt' => $stage->starts_at?->toDateTimeString(),
            'endsAt' => $stage->ends_at?->toDateTimeString(),
            'status' => $stage->status->value,
            'statusLabel' => $stage->status->label(),
            'statusTone' => $stage->status->tone(),
            'isLiveStage' => $stage->is_live_stage,
        ];
    }
}
