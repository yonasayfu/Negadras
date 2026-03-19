<?php

namespace App\Support;

use App\FeedbackVisibilityStatus;
use App\JudgeCommentType;
use App\Models\PresenterFeedbackPacket;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Str;

class FeedbackPacketBuilder
{
    public function build(Submission $submission, User $actor): PresenterFeedbackPacket
    {
        $submission->loadMissing([
            'judgeComments.judge.user:id,name',
            'rankingSnapshots',
            'reviewDecisions',
            'currentStage',
        ]);

        $presenterVisibleComments = $submission->judgeComments
            ->where('comment_type', JudgeCommentType::PresenterVisible)
            ->where('is_archived', false)
            ->pluck('content')
            ->filter(fn (?string $content): bool => filled($content))
            ->values();

        $strengths = $presenterVisibleComments->take(3)->implode("\n- ");
        $improvements = $presenterVisibleComments->slice(3, 3)->implode("\n- ");
        $latestDecision = $submission->reviewDecisions->first();
        $scoreSummary = $submission->rankingSnapshots->first()?->aggregate_score;

        return PresenterFeedbackPacket::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
            ],
            [
                'summary' => Str::of("Your submission {$submission->title} completed the {$submission->currentStage?->name} stage with status {$submission->status->value}.")
                    ->append($latestDecision?->decision_reason ? " Decision note: {$latestDecision->decision_reason}" : '')
                    ->toString(),
                'strengths' => $strengths === '' ? null : '- '.$strengths,
                'improvement_areas' => $improvements === '' ? null : '- '.$improvements,
                'next_step_guidance' => $submission->status->value === 'shortlisted'
                    ? 'Prepare for the next judging or presentation stage and keep your materials current.'
                    : 'Review the feedback, refine the proposal, and prepare a stronger revision for the next submission opportunity.',
                'generated_by' => $actor->id,
                'visibility_status' => FeedbackVisibilityStatus::InternalReview,
                'score_summary_optional' => $scoreSummary,
            ],
        );
    }
}
