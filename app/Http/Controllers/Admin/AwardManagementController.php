<?php

namespace App\Http\Controllers\Admin;

use App\AwardType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAwardRecordRequest;
use App\Http\Requests\Admin\UpdateAwardRecordRequest;
use App\Models\AwardRecord;
use App\Models\RankingSnapshot;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AwardManagementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AwardRecord::class);

        return Inertia::render('admin/Awards/Index', [
            'awards' => AwardRecord::query()
                ->with(['submission.applicant:id,full_name', 'submission.organization:id,display_name', 'season:id,name'])
                ->latest('granted_at')
                ->get()
                ->map(fn (AwardRecord $award): array => [
                    'id' => $award->id,
                    'submissionId' => $award->submission_id,
                    'submissionTitle' => $award->submission?->title,
                    'applicantName' => $award->submission?->applicant?->full_name,
                    'organizationName' => $award->submission?->organization?->display_name,
                    'seasonName' => $award->season?->name,
                    'awardType' => $award->award_type->value,
                    'awardTypeLabel' => $award->award_type->label(),
                    'rankPosition' => $award->rank_position,
                    'prizeValueOptional' => $award->prize_value_optional,
                    'notes' => $award->notes,
                    'grantedAt' => $award->granted_at?->toDateTimeString(),
                ])
                ->all(),
            'rankingOptions' => RankingSnapshot::query()
                ->with(['submission.applicant:id,full_name'])
                ->whereNotNull('finalized_at')
                ->orderBy('rank_position')
                ->get()
                ->map(fn (RankingSnapshot $snapshot): array => [
                    'value' => $snapshot->id,
                    'submissionId' => $snapshot->submission_id,
                    'seasonId' => $snapshot->season_id,
                    'rankPosition' => $snapshot->rank_position,
                    'label' => "{$snapshot->rank_position}. {$snapshot->submission?->title} · ".($snapshot->submission?->applicant?->full_name ?? 'Unknown presenter'),
                ])
                ->all(),
            'awardTypeOptions' => collect(AwardType::cases())->map(fn (AwardType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->all(),
        ]);
    }

    public function store(StoreAwardRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', AwardRecord::class);

        $award = AwardRecord::query()->create([
            ...$request->safe()->all(),
            'granted_by' => $request->user()->id,
            'granted_at' => now(),
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.awards.created',
            description: "Recorded award {$award->award_type->label()} for {$award->submission?->title}.",
            subject: $award,
            request: $request,
        );

        return back()->with('success', 'Award recorded successfully.');
    }

    public function update(UpdateAwardRecordRequest $request, AwardRecord $awardRecord): RedirectResponse
    {
        $this->authorize('update', $awardRecord);

        $awardRecord->update($request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.awards.updated',
            description: "Updated award for {$awardRecord->submission?->title}.",
            subject: $awardRecord,
            request: $request,
        );

        return back()->with('success', 'Award updated successfully.');
    }
}
