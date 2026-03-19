<?php

namespace App\Support;

use App\ConflictOfInterestStatus;
use App\JudgeCommentType;
use App\Models\Judge;
use App\Models\JudgeComment;
use App\Models\PanelSubmissionAssignment;
use App\Models\RubricCriterion;
use App\Models\ScoreEntry;
use App\Models\User;
use App\PanelSubmissionAssignmentStatus;
use Illuminate\Validation\ValidationException;

class ScoreEngine
{
    public function isLocked(PanelSubmissionAssignment $assignment): bool
    {
        $assignment->loadMissing('scoreLocks');

        $latestLock = $assignment->scoreLocks->sortByDesc('locked_at')->first();

        return $assignment->status === PanelSubmissionAssignmentStatus::Locked
            || ($latestLock !== null && $latestLock->reopened_at === null);
    }

    public function hasActiveConflict(PanelSubmissionAssignment $assignment, Judge $judge): bool
    {
        return $assignment->submission
            ->conflictDeclarations()
            ->where('judge_id', $judge->id)
            ->where('status', ConflictOfInterestStatus::Active)
            ->exists();
    }

    /**
     * @param  array<int, array{criterion_id: int, score_value: float|int|string|null, comment?: string|null}>  $scores
     */
    public function saveScores(
        PanelSubmissionAssignment $assignment,
        Judge $judge,
        array $scores,
        ?string $privateComment,
        ?string $presenterComment,
        bool $submit,
        User $actor,
    ): void {
        if ($this->isLocked($assignment)) {
            throw ValidationException::withMessages([
                'scores' => 'This scoring assignment is locked and can no longer be changed.',
            ]);
        }

        if ($this->hasActiveConflict($assignment, $judge)) {
            throw ValidationException::withMessages([
                'scores' => 'Resolve the active conflict declaration before scoring this submission.',
            ]);
        }

        $assignment->loadMissing('panel.rubric.criteria', 'panel.members');

        $criteria = $assignment->panel?->rubric?->criteria ?? collect();
        $criteriaById = $criteria->keyBy('id');

        foreach ($scores as $scorePayload) {
            $criterion = $criteriaById->get((int) $scorePayload['criterion_id']);

            if (! $criterion instanceof RubricCriterion) {
                throw ValidationException::withMessages([
                    'scores' => 'One or more score rows reference a criterion outside the panel rubric.',
                ]);
            }

            ScoreEntry::query()->updateOrCreate(
                [
                    'panel_submission_assignment_id' => $assignment->id,
                    'judge_id' => $judge->id,
                    'rubric_criterion_id' => $criterion->id,
                ],
                [
                    'submission_id' => $assignment->submission_id,
                    'panel_id_optional' => $assignment->panel_id,
                    'score_value' => $scorePayload['score_value'],
                    'comment' => $scorePayload['comment'] ?? null,
                    'is_secret' => false,
                    'is_locked' => false,
                    'submitted_at' => $submit ? now() : null,
                ],
            );
        }

        $this->syncComment($assignment, $judge, JudgeCommentType::Private, $privateComment);
        $this->syncComment($assignment, $judge, JudgeCommentType::PresenterVisible, $presenterComment);

        $assignment->unsetRelation('scoreEntries');
        $assignment->load('scoreEntries');

        $assignment->update([
            'status' => $submit && $this->allPanelJudgesSubmitted($assignment)
                ? PanelSubmissionAssignmentStatus::Submitted
                : PanelSubmissionAssignmentStatus::Scoring,
        ]);
    }

    public function judgeTotal(PanelSubmissionAssignment $assignment, Judge $judge): float
    {
        $assignment->loadMissing('panel.rubric.criteria', 'scoreEntries');

        $criteria = $assignment->panel?->rubric?->criteria ?? collect();
        $entries = $assignment->scoreEntries->where('judge_id', $judge->id)->keyBy('rubric_criterion_id');

        return round($criteria->sum(function (RubricCriterion $criterion) use ($entries): float {
            /** @var ScoreEntry|null $entry */
            $entry = $entries->get($criterion->id);

            if ($entry === null || $criterion->max_score <= 0) {
                return 0;
            }

            return ((float) $entry->score_value / (float) $criterion->max_score) * (float) $criterion->weight;
        }), 2);
    }

    public function aggregateTotal(PanelSubmissionAssignment $assignment): ?float
    {
        $assignment->loadMissing('panel.members.judge', 'scoreEntries');

        $judgeIds = $assignment->panel?->members->pluck('judge_id')->filter()->values() ?? collect();

        if ($judgeIds->isEmpty()) {
            return null;
        }

        $totals = $judgeIds->map(fn (int $judgeId): float => $this->judgeTotal($assignment, Judge::query()->findOrFail($judgeId)))
            ->filter(fn (float $total): bool => $total > 0)
            ->values();

        if ($totals->isEmpty()) {
            return null;
        }

        return round($totals->avg(), 2);
    }

    public function judgeProgress(PanelSubmissionAssignment $assignment, Judge $judge): array
    {
        $assignment->loadMissing('panel.rubric.criteria', 'scoreEntries');

        $criteriaCount = $assignment->panel?->rubric?->criteria->count() ?? 0;
        $scoredCount = $assignment->scoreEntries
            ->where('judge_id', $judge->id)
            ->whereNotNull('score_value')
            ->count();

        return [
            'criteriaCount' => $criteriaCount,
            'scoredCount' => $scoredCount,
            'isComplete' => $criteriaCount > 0 && $criteriaCount === $scoredCount,
            'total' => $this->judgeTotal($assignment, $judge),
        ];
    }

    private function allPanelJudgesSubmitted(PanelSubmissionAssignment $assignment): bool
    {
        $assignment->loadMissing('panel.members', 'panel.rubric.criteria', 'scoreEntries');

        $criteriaCount = $assignment->panel?->rubric?->criteria->count() ?? 0;

        if ($criteriaCount === 0) {
            return false;
        }

        return $assignment->panel?->members
            ->every(function ($member) use ($assignment, $criteriaCount): bool {
                return $assignment->scoreEntries
                    ->where('judge_id', $member->judge_id)
                    ->whereNotNull('submitted_at')
                    ->count() === $criteriaCount;
            }) ?? false;
    }

    private function syncComment(
        PanelSubmissionAssignment $assignment,
        Judge $judge,
        JudgeCommentType $type,
        ?string $content,
    ): void {
        $existingComment = JudgeComment::query()
            ->where('panel_submission_assignment_id', $assignment->id)
            ->where('judge_id', $judge->id)
            ->where('comment_type', $type)
            ->first();

        if (blank($content)) {
            $existingComment?->delete();

            return;
        }

        JudgeComment::query()->updateOrCreate(
            [
                'panel_submission_assignment_id' => $assignment->id,
                'judge_id' => $judge->id,
                'comment_type' => $type,
            ],
            [
                'submission_id' => $assignment->submission_id,
                'content' => $content,
                'is_archived' => false,
                'created_at' => $existingComment?->created_at ?? now(),
            ],
        );
    }
}
