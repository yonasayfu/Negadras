<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReviewerRequest;
use App\Http\Requests\Admin\UpdateReviewerRequest;
use App\Models\Reviewer;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\ReviewerAssignmentStatus;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewerManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reviewer::class);

        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/Reviewers/Index', [
            'reviewers' => Reviewer::query()
                ->with('user:id,name,email')
                ->withCount(['assignments as activeAssignmentsCount' => function ($query): void {
                    $query->whereIn('status', [
                        ReviewerAssignmentStatus::Assigned,
                        ReviewerAssignmentStatus::InProgress,
                    ]);
                }])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($reviewerQuery) use ($search): void {
                        $reviewerQuery
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
                ->orderByDesc('is_active')
                ->orderBy(
                    User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'reviewers.user_id')
                        ->limit(1),
                )
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Reviewer $reviewer): array => $this->reviewerSummary($reviewer)),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Reviewer::class);

        return Inertia::render('admin/Reviewers/Create', [
            'userOptions' => $this->availableUserOptions(),
        ]);
    }

    public function store(StoreReviewerRequest $request): RedirectResponse
    {
        $this->authorize('create', Reviewer::class);

        $user = User::query()->findOrFail($request->validated('user_id'));
        $user->assignRole('Reviewer');

        $reviewer = Reviewer::query()->create($request->validated());

        $user->notify(new SystemMessageNotification(
            title: 'Reviewer access activated',
            message: 'Your Negadras reviewer profile is now active. Assigned submissions will appear in your reviewer queue.',
            actionUrl: route('reviewer-queue.index'),
            actionLabel: 'Open reviewer queue',
        ));

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.reviewers.created',
            description: "Created reviewer profile for {$user->email}.",
            subject: $reviewer,
            request: $request,
        );

        return to_route('reviewers.edit', $reviewer)->with('success', 'Reviewer profile created successfully.');
    }

    public function edit(Reviewer $reviewer): Response
    {
        $this->authorize('view', $reviewer);

        $reviewer->load('user:id,name,email');

        return Inertia::render('admin/Reviewers/Edit', [
            'reviewer' => $this->reviewerSummary($reviewer),
        ]);
    }

    public function update(UpdateReviewerRequest $request, Reviewer $reviewer): RedirectResponse
    {
        $this->authorize('update', $reviewer);

        $reviewer->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.reviewers.updated',
            description: "Updated reviewer profile for {$reviewer->user?->email}.",
            subject: $reviewer,
            request: $request,
        );

        return to_route('reviewers.edit', $reviewer)->with('success', 'Reviewer profile updated successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableUserOptions(): array
    {
        return User::query()
            ->whereDoesntHave('reviewer')
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
    private function reviewerSummary(Reviewer $reviewer): array
    {
        return [
            'id' => $reviewer->id,
            'userId' => $reviewer->user_id,
            'name' => $reviewer->user?->name,
            'email' => $reviewer->user?->email,
            'professionalTitle' => $reviewer->professional_title,
            'organization' => $reviewer->organization,
            'specialization' => $reviewer->specialization,
            'bio' => $reviewer->bio,
            'isActive' => $reviewer->is_active,
            'activeAssignmentsCount' => $reviewer->activeAssignmentsCount ?? $reviewer->assignments()
                ->whereIn('status', [ReviewerAssignmentStatus::Assigned, ReviewerAssignmentStatus::InProgress])
                ->count(),
        ];
    }
}
