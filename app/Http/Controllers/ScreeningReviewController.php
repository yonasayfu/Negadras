<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScreeningReviewRequest;
use App\Models\ReviewerAssignment;
use App\Support\ReviewerAssignmentService;
use Illuminate\Http\RedirectResponse;

class ScreeningReviewController extends Controller
{
    public function store(
        StoreScreeningReviewRequest $request,
        ReviewerAssignment $reviewerAssignment,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('update', $reviewerAssignment);

        if ($reviewerAssignment->screeningReview?->isSubmitted()) {
            return to_route('reviewer-queue.show', $reviewerAssignment)->with('error', 'This screening review is already submitted and locked.');
        }

        $payload = $request->validated();

        if ($payload['intent'] === 'submit') {
            $assignmentService->submitReview(
                assignment: $reviewerAssignment,
                payload: $payload,
                actor: $request->user(),
            );

            return to_route('reviewer-queue.show', $reviewerAssignment)->with('success', 'Screening review submitted successfully.');
        }

        $assignmentService->saveDraftReview(
            assignment: $reviewerAssignment,
            payload: $payload,
        );

        return to_route('reviewer-queue.show', $reviewerAssignment)->with('success', 'Screening review draft saved successfully.');
    }
}
