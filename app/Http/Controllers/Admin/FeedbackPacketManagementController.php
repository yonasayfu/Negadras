<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackVisibilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendPresenterFeedbackPacketRequest;
use App\Http\Requests\Admin\StorePresenterFeedbackPacketRequest;
use App\Http\Requests\Admin\UpdatePresenterFeedbackPacketRequest;
use App\Models\PresenterFeedbackPacket;
use App\Models\Submission;
use App\Models\User;
use App\Support\FeedbackPacketBuilder;
use App\Support\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackPacketManagementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PresenterFeedbackPacket::class);

        return Inertia::render('admin/FeedbackPackets/Index', [
            'packets' => PresenterFeedbackPacket::query()
                ->with(['submission.applicant:id,full_name,user_id', 'submission.organization:id,display_name', 'stage:id,name'])
                ->latest('updated_at')
                ->get()
                ->map(fn (PresenterFeedbackPacket $packet): array => [
                    'id' => $packet->id,
                    'submissionId' => $packet->submission_id,
                    'submissionTitle' => $packet->submission?->title,
                    'applicantName' => $packet->submission?->applicant?->full_name,
                    'organizationName' => $packet->submission?->organization?->display_name,
                    'stageName' => $packet->stage?->name,
                    'summary' => $packet->summary,
                    'strengths' => $packet->strengths,
                    'improvementAreas' => $packet->improvement_areas,
                    'nextStepGuidance' => $packet->next_step_guidance,
                    'visibilityStatus' => $packet->visibility_status->value,
                    'visibilityStatusLabel' => $packet->visibility_status->label(),
                    'scoreSummaryOptional' => $packet->score_summary_optional,
                    'sentAtOptional' => $packet->sent_at_optional?->toDateTimeString(),
                ])
                ->all(),
            'submissionOptions' => Submission::query()
                ->with(['applicant:id,full_name'])
                ->where(function ($query): void {
                    $query->has('reviewDecisions')->orHas('rankingSnapshots');
                })
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (Submission $submission): array => [
                    'value' => $submission->id,
                    'label' => "{$submission->title} · ".($submission->applicant?->full_name ?? 'Unknown presenter'),
                ])
                ->all(),
            'visibilityOptions' => collect(FeedbackVisibilityStatus::cases())->map(fn (FeedbackVisibilityStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
        ]);
    }

    public function store(
        StorePresenterFeedbackPacketRequest $request,
        FeedbackPacketBuilder $builder,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $this->authorize('create', PresenterFeedbackPacket::class);

        $submission = Submission::query()->findOrFail($request->integer('submission_id'));
        $packet = $builder->build($submission, $request->user());

        $packet->fill(array_filter([
            'summary' => $request->string('summary')->toString() ?: null,
            'strengths' => $request->string('strengths')->toString() ?: null,
            'improvement_areas' => $request->string('improvement_areas')->toString() ?: null,
            'next_step_guidance' => $request->string('next_step_guidance')->toString() ?: null,
            'visibility_status' => $request->string('visibility_status')->toString() ?: null,
        ], fn ($value) => $value !== null))->save();

        if ($request->boolean('send_now')) {
            $this->deliverPacket($packet, true, $notifications, $request->user());
        }

        return back()->with('success', 'Feedback packet saved successfully.');
    }

    public function update(UpdatePresenterFeedbackPacketRequest $request, PresenterFeedbackPacket $presenterFeedbackPacket): RedirectResponse
    {
        $this->authorize('update', $presenterFeedbackPacket);

        $presenterFeedbackPacket->update([
            'summary' => $request->string('summary')->toString(),
            'strengths' => $request->string('strengths')->toString() ?: null,
            'improvement_areas' => $request->string('improvement_areas')->toString() ?: null,
            'next_step_guidance' => $request->string('next_step_guidance')->toString() ?: null,
            'visibility_status' => FeedbackVisibilityStatus::from($request->string('visibility_status')->toString()),
        ]);

        return back()->with('success', 'Feedback packet updated successfully.');
    }

    public function send(
        SendPresenterFeedbackPacketRequest $request,
        PresenterFeedbackPacket $presenterFeedbackPacket,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $this->authorize('update', $presenterFeedbackPacket);

        $this->deliverPacket($presenterFeedbackPacket, $request->boolean('send_notification', true), $notifications, $request->user());

        return back()->with('success', 'Feedback packet is now visible to the presenter.');
    }

    private function deliverPacket(
        PresenterFeedbackPacket $packet,
        bool $sendNotification,
        NotificationDispatcher $notifications,
        ?User $sender = null,
    ): void {
        $packet->update([
            'visibility_status' => FeedbackVisibilityStatus::PresenterVisible,
            'sent_at_optional' => now(),
        ]);

        if ($sendNotification && $packet->submission?->applicant?->user !== null) {
            $notifications->sendToUser(
                recipient: $packet->submission->applicant->user,
                category: 'feedback-packet',
                title: 'Feedback packet available',
                message: "A new feedback packet is available for {$packet->submission->title}.",
                actionUrl: route('feedback.show', $packet),
                actionLabel: 'Open feedback',
                level: 'success',
                sender: $sender,
                context: $packet,
            );
        }
    }
}
