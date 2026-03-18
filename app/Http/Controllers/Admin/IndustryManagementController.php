<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIndustryRequest;
use App\Http\Requests\Admin\UpdateIndustryRequest;
use App\Models\Industry;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndustryManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Industry::class);

        $search = $request->string('search')->trim()->toString();
        $active = $request->string('active')->toString();

        $industries = Industry::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($industryQuery) use ($search): void {
                    $industryQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('slug', 'ilike', "%{$search}%");
                });
            })
            ->when($active === 'active', fn ($query) => $query->where('is_active', true))
            ->when($active === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Industry $industry): array => $this->industrySummary($industry));

        return Inertia::render('admin/Industries/Index', [
            'industries' => $industries,
            'filters' => [
                'search' => $search,
                'active' => $active,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Industry::class);

        return Inertia::render('admin/Industries/Create');
    }

    public function store(StoreIndustryRequest $request): RedirectResponse
    {
        $this->authorize('create', Industry::class);

        $industry = Industry::query()->create($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.industries.created',
            description: "Created industry {$industry->name}.",
            subject: $industry,
            request: $request,
        );

        return to_route('industries.edit', $industry)->with('success', 'Industry created successfully.');
    }

    public function edit(Industry $industry): Response
    {
        $this->authorize('view', $industry);

        return Inertia::render('admin/Industries/Edit', [
            'industry' => $this->industrySummary($industry),
        ]);
    }

    public function update(UpdateIndustryRequest $request, Industry $industry): RedirectResponse
    {
        $this->authorize('update', $industry);

        $industry->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.industries.updated',
            description: "Updated industry {$industry->name}.",
            subject: $industry,
            request: $request,
        );

        return to_route('industries.edit', $industry)->with('success', 'Industry updated successfully.');
    }

    public function toggle(Request $request, Industry $industry): RedirectResponse
    {
        $this->authorize('update', $industry);

        $industry->update([
            'is_active' => ! $industry->is_active,
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.industries.toggled',
            description: sprintf('%s industry %s.', $industry->is_active ? 'Activated' : 'Deactivated', $industry->name),
            subject: $industry,
            request: $request,
        );

        return to_route('industries.index')->with('success', 'Industry status updated successfully.');
    }

    public function destroy(Request $request, Industry $industry): RedirectResponse
    {
        $this->authorize('delete', $industry);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.industries.deleted',
            description: "Deleted industry {$industry->name}.",
            subject: $industry,
            request: $request,
        );

        $industry->delete();

        return to_route('industries.index')->with('success', 'Industry deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function industrySummary(Industry $industry): array
    {
        return [
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'description' => $industry->description,
            'isActive' => $industry->is_active,
            'createdAt' => $industry->created_at?->toDateTimeString(),
        ];
    }
}
