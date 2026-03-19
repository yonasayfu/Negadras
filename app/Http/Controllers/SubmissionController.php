<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Requests\UpdateSubmissionRequest;
use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\SubmissionVersionSnapshotter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Submission::class);

        $applicant = $request->user()?->applicant;
        $submissions = collect();

        if ($applicant !== null) {
            $submissions = Submission::query()
                ->where('applicant_id', $applicant->id)
                ->with(['season:id,name', 'currentStage:id,name', 'industry:id,name', 'organization:id,display_name'])
                ->latest()
                ->get()
                ->map(fn (Submission $submission): array => $this->submissionSummary($submission));
        }

        return Inertia::render('submissions/Index', [
            'hasApplicantProfile' => $applicant !== null,
            'submissions' => $submissions->values()->all(),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', Submission::class);

        $applicant = $request->user()?->applicant;

        if ($applicant === null) {
            return to_route('applicant-profile.edit')->with('error', 'Create your presenter profile before creating a submission.');
        }

        return Inertia::render('submissions/Create', [
            'seasonOptions' => $this->seasonOptions(),
            'stageOptions' => $this->stageOptions(),
            'industryOptions' => $this->industryOptions(),
            'organizationOptions' => $this->organizationOptions($applicant),
        ]);
    }

    public function store(StoreSubmissionRequest $request, SubmissionVersionSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorize('create', Submission::class);

        $applicant = $request->user()?->applicant;

        abort_if($applicant === null, 403);

        $intent = $request->validated('intent');
        $submission = Submission::query()->create([
            ...$request->safe()->except('intent'),
            'applicant_id' => $applicant->id,
            'status' => $intent === 'submit' ? SubmissionStatus::Submitted : SubmissionStatus::Draft,
            'submitted_at' => $intent === 'submit' ? now() : null,
        ]);

        if ($intent === 'submit') {
            $snapshotter->createSnapshot(
                submission: $submission,
                actor: $request->user(),
                changeNote: 'Initial final submission.',
            );
        }

        ActivityLogger::record(
            actor: $request->user(),
            event: $intent === 'submit' ? 'negadras.submissions.submitted' : 'negadras.submissions.draft-created',
            description: $intent === 'submit'
                ? "Submitted {$submission->title}."
                : "Created draft submission {$submission->title}.",
            subject: $submission,
            properties: [
                'status' => $submission->status->value,
            ],
            request: $request,
        );

        return to_route('submissions.show', $submission)->with(
            'success',
            $intent === 'submit' ? 'Submission sent successfully.' : 'Draft saved successfully.',
        );
    }

    public function show(Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load([
            'season:id,name',
            'currentStage:id,name',
            'industry:id,name',
            'applicant:id,full_name,email',
            'organization:id,display_name',
            'currentVersion:id,submission_id,version_no,created_by,change_note,is_locked,created_at',
            'versions.creator:id,name',
        ]);

        return Inertia::render('submissions/Show', [
            'submission' => $this->submissionDetail($submission),
        ]);
    }

    public function edit(Submission $submission): Response
    {
        $this->authorize('update', $submission);

        return Inertia::render('submissions/Edit', [
            'submission' => $this->submissionDetail($submission->load([
                'season:id,name',
                'currentStage:id,name',
                'industry:id,name',
                'applicant:id,full_name,email',
                'organization:id,display_name',
            ])),
            'seasonOptions' => $this->seasonOptions(),
            'stageOptions' => $this->stageOptions(),
            'industryOptions' => $this->industryOptions(),
            'organizationOptions' => $this->organizationOptions($submission->applicant),
        ]);
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission, SubmissionVersionSnapshotter $snapshotter): RedirectResponse
    {
        $this->authorize('update', $submission);

        $intent = $request->validated('intent');
        $wasPreviouslySubmitted = $submission->status === SubmissionStatus::Submitted;
        $wasReturned = $submission->status === SubmissionStatus::IncompleteReturned;

        $submission->update([
            ...$request->safe()->except('intent'),
            'status' => $intent === 'submit'
                ? SubmissionStatus::Submitted
                : ($submission->status === SubmissionStatus::IncompleteReturned ? SubmissionStatus::IncompleteReturned : SubmissionStatus::Draft),
            'submitted_at' => $intent === 'submit' ? now() : $submission->submitted_at,
        ]);

        if ($intent === 'submit') {
            $snapshotter->createSnapshot(
                submission: $submission->fresh(),
                actor: $request->user(),
                changeNote: $wasReturned
                    ? 'Presenter resubmitted after correction.'
                    : ($wasPreviouslySubmitted ? 'Presenter submitted a new revision.' : 'Presenter finalized the submission draft.'),
            );
        }

        ActivityLogger::record(
            actor: $request->user(),
            event: $intent === 'submit' ? 'negadras.submissions.submitted' : 'negadras.submissions.updated-draft',
            description: $intent === 'submit'
                ? "Submitted {$submission->title}."
                : "Updated draft submission {$submission->title}.",
            subject: $submission,
            properties: [
                'status' => $submission->status->value,
            ],
            request: $request,
        );

        return to_route('submissions.show', $submission)->with(
            'success',
            $intent === 'submit' ? 'Submission sent successfully.' : 'Draft updated successfully.',
        );
    }

    public function destroy(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorize('delete', $submission);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.submissions.deleted-draft',
            description: "Deleted draft submission {$submission->title}.",
            subject: $submission,
            request: $request,
        );

        $submission->delete();

        return to_route('submissions.index')->with('success', 'Draft deleted successfully.');
    }

    /**
     * @return array<int, array{value: number, label: string}>
     */
    private function seasonOptions(): array
    {
        return Season::query()
            ->orderByDesc('year')
            ->get(['id', 'name', 'year'])
            ->map(fn (Season $season): array => [
                'value' => $season->id,
                'label' => sprintf('%s (%s)', $season->name, $season->year),
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: number, label: string, seasonId: number}>
     */
    private function stageOptions(): array
    {
        return Stage::query()
            ->with('season:id,name')
            ->orderBy('season_id')
            ->orderBy('order_index')
            ->get()
            ->map(fn (Stage $stage): array => [
                'value' => $stage->id,
                'label' => sprintf('%s - %s', $stage->season?->name ?? 'Season', $stage->name),
                'seasonId' => $stage->season_id,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: number, label: string}>
     */
    private function industryOptions(): array
    {
        return Industry::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Industry $industry): array => [
                'value' => $industry->id,
                'label' => $industry->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function organizationOptions(Applicant $applicant): array
    {
        return Organization::query()
            ->whereHas('teamMembers', function ($query) use ($applicant): void {
                $query
                    ->where('applicant_id', $applicant->id)
                    ->where('is_primary_contact', true);
            })
            ->orderBy('display_name')
            ->get(['id', 'display_name'])
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => $organization->display_name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionSummary(Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'title' => $submission->title,
            'seasonName' => $submission->season?->name,
            'stageName' => $submission->currentStage?->name,
            'industryName' => $submission->industry?->name,
            'organizationName' => $submission->organization?->display_name,
            'status' => $submission->status->value,
            'statusLabel' => $submission->status->label(),
            'statusTone' => $submission->status->tone(),
            'submittedAt' => $submission->submitted_at?->toDateTimeString(),
            'updatedAt' => $submission->updated_at?->toDateTimeString(),
            'canEdit' => $submission->isEditableByPresenter(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionDetail(Submission $submission): array
    {
        return [
            ...$this->submissionSummary($submission),
            'summary' => $submission->summary,
            'problemStatement' => $submission->problem_statement,
            'solutionDescription' => $submission->solution_description,
            'businessModel' => $submission->business_model,
            'seasonId' => $submission->season_id,
            'currentStageId' => $submission->current_stage_id,
            'industryId' => $submission->industry_id,
            'organizationId' => $submission->organization_id,
            'applicantName' => $submission->applicant?->full_name,
            'applicantEmail' => $submission->applicant?->email,
            'isPublicAfterApproval' => $submission->is_public_after_approval,
            'currentVersionNumber' => $submission->currentVersion?->version_no,
            'versionCount' => $submission->versions->count(),
            'versionHistory' => $submission->versions
                ->map(fn ($version): array => [
                    'id' => $version->id,
                    'versionNo' => $version->version_no,
                    'changeNote' => $version->change_note,
                    'createdAt' => $version->created_at?->toDateTimeString(),
                    'createdBy' => $version->creator?->name,
                    'isLocked' => $version->is_locked,
                    'isCurrent' => $submission->current_version_id === $version->id,
                    'snapshotTitle' => $version->snapshot_json['title'] ?? $submission->title,
                    'snapshotStatus' => Str::of($version->snapshot_json['status'] ?? 'draft')->replace('_', ' ')->title()->toString(),
                ])
                ->values()
                ->all(),
        ];
    }
}
