<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRubricRequest;
use App\Http\Requests\Admin\UpdateRubricRequest;
use App\Models\Industry;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Stage;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RubricManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Rubric::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        return Inertia::render('admin/Rubrics/Index', [
            'rubrics' => Rubric::query()
                ->withCount('criteria')
                ->with(['stages:id,name', 'industries:id,name'])
                ->when($search !== '', fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
                ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Rubric $rubric): array => $this->rubricSummary($rubric)),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Rubric::class);

        return Inertia::render('admin/Rubrics/Create', [
            'stageOptions' => $this->stageOptions(),
            'industryOptions' => $this->industryOptions(),
        ]);
    }

    public function store(StoreRubricRequest $request): RedirectResponse
    {
        $this->authorize('create', Rubric::class);

        $validated = $request->validated();

        $rubric = Rubric::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
            'total_weight' => collect($validated['criteria'])->sum('weight'),
        ]);

        $this->syncCriteria($rubric, $validated['criteria']);
        $rubric->stages()->sync($validated['stage_ids'] ?? []);
        $rubric->industries()->sync($validated['industry_ids'] ?? []);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.rubrics.created',
            description: "Created rubric {$rubric->name}.",
            subject: $rubric,
            request: $request,
        );

        return to_route('rubrics.edit', $rubric)->with('success', 'Rubric created successfully.');
    }

    public function edit(Rubric $rubric): Response
    {
        $this->authorize('view', $rubric);

        $rubric->load(['criteria', 'stages:id,name', 'industries:id,name']);

        return Inertia::render('admin/Rubrics/Edit', [
            'rubric' => $this->rubricDetail($rubric),
            'stageOptions' => $this->stageOptions(),
            'industryOptions' => $this->industryOptions(),
        ]);
    }

    public function update(UpdateRubricRequest $request, Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        $validated = $request->validated();

        $rubric->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
            'total_weight' => collect($validated['criteria'])->sum('weight'),
        ]);

        $this->syncCriteria($rubric, $validated['criteria']);
        $rubric->stages()->sync($validated['stage_ids'] ?? []);
        $rubric->industries()->sync($validated['industry_ids'] ?? []);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.rubrics.updated',
            description: "Updated rubric {$rubric->name}.",
            subject: $rubric,
            request: $request,
        );

        return to_route('rubrics.edit', $rubric)->with('success', 'Rubric updated successfully.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $criteriaPayload
     */
    private function syncCriteria(Rubric $rubric, array $criteriaPayload): void
    {
        $rubric->loadMissing('criteria');

        $existingIds = $rubric->criteria->pluck('id');
        $incomingIds = collect($criteriaPayload)->pluck('id')->filter()->map(fn ($id) => (int) $id);

        RubricCriterion::query()
            ->where('rubric_id', $rubric->id)
            ->whereIn('id', $existingIds->diff($incomingIds))
            ->delete();

        foreach ($criteriaPayload as $criterionPayload) {
            RubricCriterion::query()->updateOrCreate(
                [
                    'id' => $criterionPayload['id'] ?? null,
                    'rubric_id' => $rubric->id,
                ],
                [
                    'name' => $criterionPayload['name'],
                    'description' => $criterionPayload['description'] ?? null,
                    'max_score' => $criterionPayload['max_score'],
                    'weight' => $criterionPayload['weight'],
                    'order_index' => $criterionPayload['order_index'],
                    'is_required' => $criterionPayload['is_required'] ?? false,
                    'visibility_rule' => $criterionPayload['visibility_rule'] ?? null,
                    'help_text' => $criterionPayload['help_text'] ?? null,
                ],
            );
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function stageOptions(): array
    {
        return Stage::query()
            ->with('season:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'season_id'])
            ->map(fn (Stage $stage): array => [
                'value' => (string) $stage->id,
                'label' => "{$stage->name} ({$stage->season?->name})",
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function industryOptions(): array
    {
        return Industry::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Industry $industry): array => [
                'value' => (string) $industry->id,
                'label' => $industry->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function rubricSummary(Rubric $rubric): array
    {
        return [
            'id' => $rubric->id,
            'name' => $rubric->name,
            'description' => $rubric->description,
            'totalWeight' => (float) $rubric->total_weight,
            'isActive' => $rubric->is_active,
            'criteriaCount' => $rubric->criteria_count ?? $rubric->criteria()->count(),
            'stageNames' => $rubric->stages->pluck('name')->all(),
            'industryNames' => $rubric->industries->pluck('name')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rubricDetail(Rubric $rubric): array
    {
        return [
            ...$this->rubricSummary($rubric),
            'stageIds' => $rubric->stages->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            'industryIds' => $rubric->industries->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            'criteria' => $rubric->criteria
                ->sortBy('order_index')
                ->values()
                ->map(fn (RubricCriterion $criterion): array => [
                    'id' => $criterion->id,
                    'name' => $criterion->name,
                    'description' => $criterion->description,
                    'maxScore' => (float) $criterion->max_score,
                    'weight' => (float) $criterion->weight,
                    'orderIndex' => $criterion->order_index,
                    'isRequired' => $criterion->is_required,
                    'visibilityRule' => $criterion->visibility_rule,
                    'helpText' => $criterion->help_text,
                ])
                ->all(),
        ];
    }
}
