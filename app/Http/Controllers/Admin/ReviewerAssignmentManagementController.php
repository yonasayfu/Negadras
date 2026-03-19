<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReassignReviewerAssignmentRequest;
use App\Http\Requests\Admin\StoreBulkReviewerAssignmentsRequest;
use App\Http\Requests\Admin\StoreReviewerAssignmentRequest;
use App\Http\Requests\Admin\StoreTechnicalReviewerAssignmentRequest;
use App\Http\Requests\TransitionReviewerAssignmentRequest;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\ReviewAssignmentType;
use App\Support\ReviewerAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewerAssignmentManagementController extends Controller
{
    public function storeBulk(
        StoreBulkReviewerAssignmentsRequest $request,
        Submission $submission,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('create', ReviewerAssignment::class);

        $reviewers = Reviewer::query()
            ->whereIn('id', $request->validated('reviewer_ids'))
            ->get();

        $assignmentService->bulkAssign(
            submission: $submission,
            reviewers: $reviewers->all(),
            actor: $request->user(),
            dueAt: $request->validated('due_at'),
            assignmentType: ReviewAssignmentType::Screening,
        );

        return to_route('admin-submissions.show', $submission)->with('success', 'Reviewers assigned successfully.');
    }

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
            assignmentType: ReviewAssignmentType::Screening,
        );

        return to_route('admin-submissions.show', $submission)->with('success', 'Reviewer assigned successfully.');
    }

    public function storeTechnical(
        StoreTechnicalReviewerAssignmentRequest $request,
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
            assignmentType: ReviewAssignmentType::Technical,
        );

        return to_route('technical-queue.show', $submission)->with('success', 'Technical reviewer assigned successfully.');
    }

    public function storeTechnicalBulk(
        StoreBulkReviewerAssignmentsRequest $request,
        Submission $submission,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('create', ReviewerAssignment::class);

        $reviewers = Reviewer::query()
            ->whereIn('id', $request->validated('reviewer_ids'))
            ->get();

        $assignmentService->bulkAssign(
            submission: $submission,
            reviewers: $reviewers->all(),
            actor: $request->user(),
            dueAt: $request->validated('due_at'),
            assignmentType: ReviewAssignmentType::Technical,
        );

        return to_route('technical-queue.show', $submission)->with('success', 'Technical reviewers assigned successfully.');
    }

    public function update(
        ReassignReviewerAssignmentRequest $request,
        ReviewerAssignment $reviewerAssignment,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('update', $reviewerAssignment);

        $reviewer = Reviewer::query()->findOrFail($request->validated('reviewer_id'));

        $assignmentService->reassign(
            assignment: $reviewerAssignment,
            reviewer: $reviewer,
            actor: $request->user(),
            dueAt: $request->validated('due_at'),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'Reviewer assignment updated successfully.');
    }

    public function transition(
        TransitionReviewerAssignmentRequest $request,
        ReviewerAssignment $reviewerAssignment,
        ReviewerAssignmentService $assignmentService,
    ): RedirectResponse {
        $this->authorize('update', $reviewerAssignment);

        $assignmentService->reopenReview(
            assignment: $reviewerAssignment,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return back()->with('success', 'Reviewer assignment reopened successfully.');
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
