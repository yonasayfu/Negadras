<?php

namespace App\Http\Controllers;

use App\FeedbackVisibilityStatus;
use App\Models\PresenterFeedbackPacket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresenterFeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $packets = PresenterFeedbackPacket::query()
            ->with(['submission.currentStage:id,name', 'submission.organization:id,display_name'])
            ->whereHas('submission.applicant', fn ($query) => $query->where('user_id', $request->user()->id))
            ->where('visibility_status', FeedbackVisibilityStatus::PresenterVisible)
            ->latest('sent_at_optional')
            ->get();

        return Inertia::render('feedback/Index', [
            'packets' => $packets->map(fn (PresenterFeedbackPacket $packet): array => [
                'id' => $packet->id,
                'submissionTitle' => $packet->submission?->title,
                'stageName' => $packet->submission?->currentStage?->name,
                'organizationName' => $packet->submission?->organization?->display_name,
                'sentAtOptional' => $packet->sent_at_optional?->toDateTimeString(),
                'scoreSummaryOptional' => $packet->score_summary_optional,
                'summary' => $packet->summary,
            ])->all(),
        ]);
    }

    public function show(PresenterFeedbackPacket $presenterFeedbackPacket): Response
    {
        $this->authorize('view', $presenterFeedbackPacket);

        $presenterFeedbackPacket->load(['submission.currentStage:id,name', 'submission.organization:id,display_name']);

        return Inertia::render('feedback/Show', [
            'packet' => [
                'id' => $presenterFeedbackPacket->id,
                'submissionTitle' => $presenterFeedbackPacket->submission?->title,
                'stageName' => $presenterFeedbackPacket->submission?->currentStage?->name,
                'organizationName' => $presenterFeedbackPacket->submission?->organization?->display_name,
                'summary' => $presenterFeedbackPacket->summary,
                'strengths' => $presenterFeedbackPacket->strengths,
                'improvementAreas' => $presenterFeedbackPacket->improvement_areas,
                'nextStepGuidance' => $presenterFeedbackPacket->next_step_guidance,
                'scoreSummaryOptional' => $presenterFeedbackPacket->score_summary_optional,
                'sentAtOptional' => $presenterFeedbackPacket->sent_at_optional?->toDateTimeString(),
            ],
        ]);
    }
}
