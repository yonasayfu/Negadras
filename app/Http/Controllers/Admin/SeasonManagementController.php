<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Models\Season;
use App\SeasonStatus;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Season::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $seasons = Season::query()
            ->withCount('stages')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($seasonQuery) use ($search): void {
                    $seasonQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('slug', 'ilike', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('year')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Season $season): array => $this->seasonSummary($season));

        return Inertia::render('admin/Seasons/Index', [
            'seasons' => $seasons,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => $this->seasonStatusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Season::class);

        return Inertia::render('admin/Seasons/Create', [
            'statusOptions' => $this->seasonStatusOptions(),
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $this->authorize('create', Season::class);

        $season = Season::query()->create($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.seasons.created',
            description: "Created season {$season->name}.",
            subject: $season,
            properties: [
                'status' => $season->status->value,
            ],
            request: $request,
        );

        return to_route('seasons.edit', $season)->with('success', 'Season created successfully.');
    }

    public function edit(Season $season): Response
    {
        $this->authorize('view', $season);

        $season->loadCount('stages');

        return Inertia::render('admin/Seasons/Edit', [
            'season' => $this->seasonSummary($season),
            'statusOptions' => $this->seasonStatusOptions(),
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $this->authorize('update', $season);

        $season->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.seasons.updated',
            description: "Updated season {$season->name}.",
            subject: $season,
            properties: [
                'status' => $season->status->value,
            ],
            request: $request,
        );

        return to_route('seasons.edit', $season)->with('success', 'Season updated successfully.');
    }

    public function activate(Request $request, Season $season): RedirectResponse
    {
        $this->authorize('update', $season);

        $season->update(['status' => SeasonStatus::Active]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.seasons.activated',
            description: "Activated season {$season->name}.",
            subject: $season,
            request: $request,
        );

        return to_route('seasons.index')->with('success', 'Season activated successfully.');
    }

    public function close(Request $request, Season $season): RedirectResponse
    {
        $this->authorize('update', $season);

        $season->update(['status' => SeasonStatus::Closed]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.seasons.closed',
            description: "Closed season {$season->name}.",
            subject: $season,
            request: $request,
        );

        return to_route('seasons.index')->with('success', 'Season closed successfully.');
    }

    public function destroy(Request $request, Season $season): RedirectResponse
    {
        $this->authorize('delete', $season);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.seasons.deleted',
            description: "Deleted season {$season->name}.",
            subject: $season,
            request: $request,
        );

        $season->delete();

        return to_route('seasons.index')->with('success', 'Season deleted successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function seasonStatusOptions(): array
    {
        return collect(SeasonStatus::cases())
            ->map(fn (SeasonStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function seasonSummary(Season $season): array
    {
        return [
            'id' => $season->id,
            'name' => $season->name,
            'year' => $season->year,
            'slug' => $season->slug,
            'status' => $season->status->value,
            'statusLabel' => $season->status->label(),
            'statusTone' => $season->status->tone(),
            'registrationOpenAt' => $season->registration_open_at?->toDateTimeString(),
            'registrationCloseAt' => $season->registration_close_at?->toDateTimeString(),
            'description' => $season->description,
            'stagesCount' => $season->stages_count ?? 0,
            'createdAt' => $season->created_at?->toDateTimeString(),
        ];
    }
}
