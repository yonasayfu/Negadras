<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTechnicalReviewRequest;
use App\Models\ReviewerAssignment;
use App\Support\ReviewerAssignmentService;
use Illuminate\Http\RedirectResponse;

class TechnicalReviewController extends Controller
{
    public function store(
        StoreTechnicalReviewRequest $request,
        ReviewerAssignment $reviewerAssignment,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('update', $reviewerAssignment);

        abort_unless($reviewerAssignment->isTechnical(), 404);

        if ($reviewerAssignment->technicalReview?->isSubmitted()) {
            return to_route('technical-reviewer-queue.show', $reviewerAssignment)->with('error', 'This technical review is already submitted and locked.');
        }

        $payload = $request->validated();

        if ($payload['intent'] === 'submit') {
            $assignmentService->submitTechnicalReview(
                assignment: $reviewerAssignment,
                payload: $payload,
                actor: $request->user(),
            );

            return to_route('technical-reviewer-queue.show', $reviewerAssignment)->with('success', 'Technical review submitted successfully.');
        }

        $assignmentService->saveDraftTechnicalReview(
            assignment: $reviewerAssignment,
            payload: $payload,
        );

        return to_route('technical-reviewer-queue.show', $reviewerAssignment)->with('success', 'Technical review draft saved successfully.');
    }
}
