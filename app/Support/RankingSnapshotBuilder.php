<?php

namespace App\Support;

use App\Models\CompetitionSession;
use App\Models\PanelSubmissionAssignment;
use App\Models\RankingSnapshot;
use App\Models\ShortlistRecord;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;

class RankingSnapshotBuilder
{
    public function __construct(
        protected ScoreEngine $scoreEngine,
    ) {}

    /**
     * @return Collection<int, RankingSnapshot>
     */
    public function generate(Stage $stage, ?CompetitionSession $competitionSession, User $actor): Collection
    {
        $assignments = PanelSubmissionAssignment::query()
            ->with(['submission', 'panel.stage', 'panel.members.judge', 'panel.rubric.criteria', 'scoreEntries'])
            ->whereHas('panel', fn ($query) => $query->where('stage_id', $stage->id))
            ->when(
                $competitionSession !== null,
                fn ($query) => $query->where('session_id_optional', $competitionSession->id),
            )
            ->latest('assigned_at')
            ->get()
            ->unique('submission_id')
            ->values();

        $shortlistRanks = ShortlistRecord::query()
            ->where('stage_id', $stage->id)
            ->get()
            ->keyBy('submission_id');

        $scoredRows = $assignments
            ->map(function (PanelSubmissionAssignment $assignment) use ($shortlistRanks): ?array {
                $aggregateScore = $this->scoreEngine->aggregateTotal($assignment);

                if ($aggregateScore === null) {
                    return null;
                }

                /** @var Submission $submission */
                $submission = $assignment->submission;
                $shortlistRecord = $shortlistRanks->get($submission->id);

                return [
                    'assignment' => $assignment,
                    'submission' => $submission,
                    'aggregateScore' => $aggregateScore,
                    'shortlistRank' => $shortlistRecord?->rank_order_optional,
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                if ($left['aggregateScore'] !== $right['aggregateScore']) {
                    return $left['aggregateScore'] < $right['aggregateScore'] ? 1 : -1;
                }

                if ($left['shortlistRank'] !== null && $right['shortlistRank'] !== null && $left['shortlistRank'] !== $right['shortlistRank']) {
                    return $left['shortlistRank'] <=> $right['shortlistRank'];
                }

                return strcmp($left['submission']->title, $right['submission']->title);
            })
            ->values();

        return $scoredRows->map(function (array $row, int $index) use ($competitionSession, $stage, $scoredRows): RankingSnapshot {
            return RankingSnapshot::query()->updateOrCreate(
                [
                    'stage_id' => $stage->id,
                    'competition_session_id' => $competitionSession?->id,
                    'submission_id' => $row['submission']->id,
                ],
                [
                    'season_id' => $row['submission']->season_id,
                    'aggregate_score' => $row['aggregateScore'],
                    'rank_position' => $index + 1,
                    'tie_break_reason_optional' => $this->tieBreakReason($index, $row, $scoredRows ?? collect()),
                    'override_reason_optional' => null,
                    'overridden_by' => null,
                ],
            );
        });
    }

    public function finalize(Stage $stage, ?CompetitionSession $competitionSession): void
    {
        RankingSnapshot::query()
            ->where('stage_id', $stage->id)
            ->where('competition_session_id', $competitionSession?->id)
            ->update([
                'finalized_at' => now(),
            ]);
    }

    /**
     * @param  Collection<int, array{assignment: PanelSubmissionAssignment, submission: Submission, aggregateScore: float, shortlistRank: int|null}>  $rows
     */
    private function tieBreakReason(int $index, array $row, Collection $rows): ?string
    {
        if ($index === 0) {
            return null;
        }

        $previous = $rows->get($index - 1);

        if ($previous === null || $previous['aggregateScore'] !== $row['aggregateScore']) {
            return null;
        }

        if ($previous['shortlistRank'] !== null || $row['shortlistRank'] !== null) {
            return 'Resolved by prior shortlist ordering.';
        }

        return 'Resolved alphabetically because no prior shortlist ranking existed.';
    }
}
