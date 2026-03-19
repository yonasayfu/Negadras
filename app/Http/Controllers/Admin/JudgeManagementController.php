<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJudgeRequest;
use App\Http\Requests\Admin\UpdateJudgeRequest;
use App\Models\Judge;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JudgeManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Judge::class);

        $search = $request->string('search')->trim()->toString();
        $specialization = $request->string('specialization')->trim()->toString();

        return Inertia::render('admin/Judges/Index', [
            'judges' => Judge::query()
                ->with('user:id,name,email')
                ->withCount('panelMembers')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($judgeQuery) use ($search): void {
                        $judgeQuery
                            ->where('professional_title', 'ilike', "%{$search}%")
                            ->orWhere('organization', 'ilike', "%{$search}%")
                            ->orWhere('specialization', 'ilike', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search): void {
                                $userQuery
                                    ->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('email', 'ilike', "%{$search}%");
                            });
                    });
                })
                ->when($specialization !== '', fn ($query) => $query->where('specialization', 'ilike', "%{$specialization}%"))
                ->orderByDesc('is_active')
                ->orderBy(
                    User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'judges.user_id')
                        ->limit(1),
                )
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Judge $judge): array => $this->judgeSummary($judge)),
            'filters' => [
                'search' => $search,
                'specialization' => $specialization,
            ],
            'specializationOptions' => Judge::query()
                ->whereNotNull('specialization')
                ->where('specialization', '!=', '')
                ->distinct()
                ->orderBy('specialization')
                ->pluck('specialization')
                ->map(fn (string $value): array => ['value' => $value, 'label' => $value])
                ->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Judge::class);

        return Inertia::render('admin/Judges/Create', [
            'userOptions' => $this->availableUserOptions(),
        ]);
    }

    public function store(StoreJudgeRequest $request): RedirectResponse
    {
        $this->authorize('create', Judge::class);

        $user = User::query()->findOrFail($request->validated('user_id'));
        $user->assignRole('Judge');

        $judge = Judge::query()->create($request->validated());

        $user->notify(new SystemMessageNotification(
            title: 'Judge access activated',
            message: 'Your Negadras judge profile is now active. Panel assignments will appear in your judge workspace.',
            actionUrl: route('judge-workspace.index'),
            actionLabel: 'Open judge workspace',
        ));

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.judges.created',
            description: "Created judge profile for {$user->email}.",
            subject: $judge,
            request: $request,
        );

        return to_route('judges.edit', $judge)->with('success', 'Judge profile created successfully.');
    }

    public function edit(Judge $judge): Response
    {
        $this->authorize('view', $judge);

        $judge->load('user:id,name,email');

        return Inertia::render('admin/Judges/Edit', [
            'judge' => $this->judgeSummary($judge),
        ]);
    }

    public function update(UpdateJudgeRequest $request, Judge $judge): RedirectResponse
    {
        $this->authorize('update', $judge);

        $judge->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.judges.updated',
            description: "Updated judge profile for {$judge->user?->email}.",
            subject: $judge,
            request: $request,
        );

        return to_route('judges.edit', $judge)->with('success', 'Judge profile updated successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableUserOptions(): array
    {
        return User::query()
            ->whereDoesntHave('judge')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'value' => (string) $user->id,
                'label' => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function judgeSummary(Judge $judge): array
    {
        return [
            'id' => $judge->id,
            'userId' => $judge->user_id,
            'name' => $judge->user?->name,
            'email' => $judge->user?->email,
            'professionalTitle' => $judge->professional_title,
            'organization' => $judge->organization,
            'specialization' => $judge->specialization,
            'bio' => $judge->bio,
            'isActive' => $judge->is_active,
            'panelMembershipsCount' => $judge->panel_members_count ?? $judge->panelMembers()->count(),
        ];
    }
}
