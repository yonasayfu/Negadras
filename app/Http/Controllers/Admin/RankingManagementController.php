<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateRankingSnapshotRequest;
use App\Http\Requests\Admin\UpdateRankingSnapshotRequest;
use App\Models\CompetitionSession;
use App\Models\RankingSnapshot;
use App\Models\Stage;
use App\OverrideEventType;
use App\Support\ActivityLogger;
use App\Support\GovernanceRecorder;
use App\Support\RankingSnapshotBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RankingSnapshot::class);

        $stageId = $request->integer('stage_id');
        $sessionId = $request->integer('competition_session_id');

        return Inertia::render('admin/Rankings/Index', [
            'records' => RankingSnapshot::query()
                ->with([
                    'submission.applicant:id,full_name',
                    'submission.organization:id,display_name',
                    'season:id,name',
                    'stage:id,name',
                    'competitionSession:id,name',
                ])
                ->when($stageId > 0, fn ($query) => $query->where('stage_id', $stageId))
                ->when($sessionId > 0, fn ($query) => $query->where('competition_session_id', $sessionId))
                ->orderBy('rank_position')
                ->get()
                ->map(fn (RankingSnapshot $snapshot): array => [
                    'id' => $snapshot->id,
                    'submissionId' => $snapshot->submission_id,
                    'submissionTitle' => $snapshot->submission?->title,
                    'applicantName' => $snapshot->submission?->applicant?->full_name,
                    'organizationName' => $snapshot->submission?->organization?->display_name,
                    'seasonId' => $snapshot->season_id,
                    'seasonName' => $snapshot->season?->name,
                    'stageId' => $snapshot->stage_id,
                    'stageName' => $snapshot->stage?->name,
                    'competitionSessionId' => $snapshot->competition_session_id,
                    'sessionName' => $snapshot->competitionSession?->name,
                    'aggregateScore' => (float) $snapshot->aggregate_score,
                    'rankPosition' => $snapshot->rank_position,
                    'tieBreakReasonOptional' => $snapshot->tie_break_reason_optional,
                    'overrideReasonOptional' => $snapshot->override_reason_optional,
                    'finalizedAt' => $snapshot->finalized_at?->toDateTimeString(),
                ])
                ->all(),
            'filters' => [
                'stageId' => $stageId > 0 ? (string) $stageId : '',
                'competitionSessionId' => $sessionId > 0 ? (string) $sessionId : '',
            ],
            'stageOptions' => Stage::query()->orderBy('name')->get(['id', 'name'])->map(fn (Stage $stage): array => [
                'value' => (string) $stage->id,
                'label' => $stage->name,
            ])->all(),
            'sessionOptions' => CompetitionSession::query()->orderByDesc('scheduled_at')->get(['id', 'name'])->map(fn (CompetitionSession $session): array => [
                'value' => (string) $session->id,
                'label' => $session->name,
            ])->all(),
        ]);
    }

    public function store(
        GenerateRankingSnapshotRequest $request,
        RankingSnapshotBuilder $builder,
    ): RedirectResponse {
        $this->authorize('create', RankingSnapshot::class);

        $stage = Stage::query()->findOrFail($request->integer('stage_id'));
        $session = $request->integer('competition_session_id') > 0
            ? CompetitionSession::query()->findOrFail($request->integer('competition_session_id'))
            : null;

        $builder->generate($stage, $session, $request->user());

        if ($request->boolean('finalize')) {
            $builder->finalize($stage, $session);
        }

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.rankings.generated',
            description: "Generated ranking snapshot for {$stage->name}.",
            subject: $stage,
            properties: [
                'competition_session_id' => $session?->id,
                'finalized' => $request->boolean('finalize'),
            ],
            request: $request,
        );

        return to_route('rankings.index', [
            'stage_id' => $stage->id,
            'competition_session_id' => $session?->id,
        ])->with('success', 'Ranking snapshot generated successfully.');
    }

    public function update(
        UpdateRankingSnapshotRequest $request,
        RankingSnapshot $rankingSnapshot,
        GovernanceRecorder $governance,
    ): RedirectResponse {
        $this->authorize('update', $rankingSnapshot);

        $beforeState = [
            'rank_position' => $rankingSnapshot->rank_position,
            'override_reason_optional' => $rankingSnapshot->override_reason_optional,
        ];

        $rankingSnapshot->update([
            'rank_position' => $request->integer('rank_position'),
            'override_reason_optional' => $request->string('override_reason_optional')->toString() ?: null,
            'overridden_by' => $request->user()->id,
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.rankings.updated',
            description: "Updated ranking override for {$rankingSnapshot->submission?->title}.",
            subject: $rankingSnapshot,
            properties: [
                'rank_position' => $rankingSnapshot->rank_position,
            ],
            request: $request,
        );

        $governance->record(
            eventType: OverrideEventType::RankingOverride,
            actor: $request->user(),
            reason: $rankingSnapshot->override_reason_optional,
            submission: $rankingSnapshot->submission,
            beforeState: $beforeState,
            afterState: [
                'rank_position' => $rankingSnapshot->rank_position,
                'override_reason_optional' => $rankingSnapshot->override_reason_optional,
            ],
        );

        return back()->with('success', 'Ranking record updated successfully.');
    }
}
