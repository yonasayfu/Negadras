<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReviewerAssignmentRequest;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Support\ReviewerAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewerAssignmentManagementController extends Controller
{
    public function store(
        StoreReviewerAssignmentRequest $request,
        Submission $submission,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('create', ReviewerAssignment::class);

        $reviewer = Reviewer::query()->findOrFail($request->validated('reviewer_id'));

        $assignmentService->assign(
            submission: $submission,
            reviewer: $reviewer,
            actor: $request->user(),
            dueAt: $request->validated('due_at'),
        );

        return to_route('admin-submissions.show', $submission)->with('success', 'Reviewer assigned successfully.');
    }

    public function destroy(
        Request $request,
        Submission $submission,
        ReviewerAssignment $reviewerAssignment,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('delete', $reviewerAssignment);

        $assignmentService->cancel($reviewerAssignment, $request->user());

        return to_route('admin-submissions.show', $submission)->with('success', 'Reviewer assignment cancelled successfully.');
    }
}
