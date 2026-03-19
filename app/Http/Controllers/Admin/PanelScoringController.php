<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePanelSubmissionAssignmentRequest;
use App\Http\Requests\Admin\ToggleScoreVisibilityRequest;
use App\Http\Requests\Admin\UpdateConflictDeclarationRequest;
use App\Http\Requests\Admin\UpdateScoreLockRequest;
use App\Models\ConflictOfInterestDeclaration;
use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\ScoreLock;
use App\Models\ScoreVisibilityEvent;
use App\Models\Submission;
use App\OverrideEventType;
use App\PanelSubmissionAssignmentStatus;
use App\ScoreVisibilityAction;
use App\SubmissionStatus;
use App\Support\ActivityLogger;
use App\Support\GovernanceRecorder;
use App\Support\ScoreEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PanelScoringController extends Controller
{
    public function storeAssignment(StorePanelSubmissionAssignmentRequest $request, Panel $panel): RedirectResponse
    {
        $this->authorize('update', $panel);

        $submission = Submission::query()->findOrFail($request->validated('submission_id'));

        if ((int) $submission->current_stage_id !== (int) $panel->stage_id) {
            throw ValidationException::withMessages([
                'submission_id' => 'The selected submission must belong to the same stage as the panel.',
            ]);
        }

        if (! in_array($submission->status, [SubmissionStatus::Eligible, SubmissionStatus::Shortlisted], true)) {
            throw ValidationException::withMessages([
                'submission_id' => 'Only eligible or shortlisted submissions can enter a judging panel.',
            ]);
        }

        $assignment = PanelSubmissionAssignment::query()->create([
            'panel_id' => $panel->id,
            'submission_id' => $submission->id,
            'assigned_at' => now(),
            'status' => PanelSubmissionAssignmentStatus::Assigned,
        ]);

        $panel->loadMissing('members.judge.user');

        $panel->members->each(function ($member) use ($assignment, $panel): void {
            $member->judge?->user?->notify(new SystemMessageNotification(
                title: 'New judging assignment',
                message: "{$assignment->submission?->title} was assigned to {$panel->name}.",
                actionUrl: route('judge-workspace.show', $assignment),
                actionLabel: 'Open judging workspace',
            ));
        });

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.panel-assignments.created',
            description: "Assigned {$submission->title} to panel {$panel->name}.",
            subject: $assignment,
            request: $request,
        );

        return to_route('panels.show', $panel)->with('success', 'Submission assigned to panel successfully.');
    }

    public function show(PanelSubmissionAssignment $panelSubmissionAssignment, ScoreEngine $scoreEngine): Response
    {
        $this->authorize('view', $panelSubmissionAssignment->panel);

        $panelSubmissionAssignment->load([
            'panel.season:id,name',
            'panel.stage:id,name',
            'panel.rubric.criteria',
            'panel.members.judge.user:id,name,email',
            'submission.applicant:id,full_name,email',
            'submission.organization:id,display_name',
            'scoreEntries.judge.user:id,name,email',
            'scoreEntries.criterion:id,name,max_score,weight',
            'judgeComments.judge.user:id,name',
            'scoreLocks.locker:id,name',
            'scoreLocks.reopener:id,name',
            'visibilityEvents.actor:id,name',
            'submission.conflictDeclarations.judge.user:id,name,email',
        ]);

        return Inertia::render('admin/Panels/Scoring', [
            'assignment' => [
                'id' => $panelSubmissionAssignment->id,
                'panelName' => $panelSubmissionAssignment->panel?->name,
                'seasonName' => $panelSubmissionAssignment->panel?->season?->name,
                'stageName' => $panelSubmissionAssignment->panel?->stage?->name,
                'submissionId' => $panelSubmissionAssignment->submission_id,
                'title' => $panelSubmissionAssignment->submission?->title,
                'applicantName' => $panelSubmissionAssignment->submission?->applicant?->full_name,
                'organizationName' => $panelSubmissionAssignment->submission?->organization?->display_name,
                'status' => $panelSubmissionAssignment->status->value,
                'statusLabel' => $panelSubmissionAssignment->status->label(),
                'statusTone' => $panelSubmissionAssignment->status->tone(),
                'assignedAt' => $panelSubmissionAssignment->assigned_at?->toDateTimeString(),
                'isLocked' => $scoreEngine->isLocked($panelSubmissionAssignment),
                'aggregateScore' => $scoreEngine->aggregateTotal($panelSubmissionAssignment),
                'criteria' => $panelSubmissionAssignment->panel?->rubric?->criteria
                    ->map(fn ($criterion): array => [
                        'id' => $criterion->id,
                        'name' => $criterion->name,
                        'weight' => (float) $criterion->weight,
                        'maxScore' => (float) $criterion->max_score,
                    ])
                    ->all() ?? [],
                'judges' => $panelSubmissionAssignment->panel?->members
                    ->map(function ($member) use ($panelSubmissionAssignment, $scoreEngine): array {
                        $judge = $member->judge;
                        $progress = $judge === null
                            ? ['criteriaCount' => 0, 'scoredCount' => 0, 'isComplete' => false, 'total' => 0]
                            : $scoreEngine->judgeProgress($panelSubmissionAssignment, $judge);

                        return [
                            'judgeId' => $member->judge_id,
                            'name' => $judge?->user?->name,
                            'email' => $judge?->user?->email,
                            'roleLabel' => $member->role_in_panel->label(),
                            'criteriaCount' => $progress['criteriaCount'],
                            'scoredCount' => $progress['scoredCount'],
                            'isComplete' => $progress['isComplete'],
                            'total' => $progress['total'],
                            'scores' => $panelSubmissionAssignment->scoreEntries
                                ->where('judge_id', $member->judge_id)
                                ->map(fn ($entry): array => [
                                    'criterionName' => $entry->criterion?->name,
                                    'scoreValue' => (float) $entry->score_value,
                                    'comment' => $entry->comment,
                                    'submittedAt' => $entry->submitted_at?->toDateTimeString(),
                                ])
                                ->values()
                                ->all(),
                            'comments' => $panelSubmissionAssignment->judgeComments
                                ->where('judge_id', $member->judge_id)
                                ->map(fn ($comment): array => [
                                    'type' => $comment->comment_type->value,
                                    'typeLabel' => $comment->comment_type->label(),
                                    'content' => $comment->content,
                                ])
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all() ?? [],
                'conflicts' => $panelSubmissionAssignment->submission?->conflictDeclarations
                    ->map(fn (ConflictOfInterestDeclaration $declaration): array => [
                        'id' => $declaration->id,
                        'judgeName' => $declaration->judge?->user?->name,
                        'typeLabel' => $declaration->conflict_type->label(),
                        'description' => $declaration->description,
                        'status' => $declaration->status->value,
                        'statusLabel' => $declaration->status->label(),
                        'declaredAt' => $declaration->declared_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all() ?? [],
                'locks' => $panelSubmissionAssignment->scoreLocks
                    ->map(fn ($lock): array => [
                        'lockedAt' => $lock->locked_at?->toDateTimeString(),
                        'lockedBy' => $lock->locker?->name,
                        'reason' => $lock->reason,
                        'reopenedAt' => $lock->reopened_at?->toDateTimeString(),
                        'reopenedBy' => $lock->reopener?->name,
                        'reopenReason' => $lock->reopen_reason,
                    ])
                    ->values()
                    ->all(),
                'visibilityEvents' => $panelSubmissionAssignment->visibilityEvents
                    ->map(fn ($event): array => [
                        'action' => $event->action->value,
                        'actionLabel' => $event->action->label(),
                        'note' => $event->note,
                        'changedAt' => $event->changed_at?->toDateTimeString(),
                        'changedBy' => $event->actor?->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'visibilityActionOptions' => collect(ScoreVisibilityAction::cases())->map(fn ($action): array => [
                'value' => $action->value,
                'label' => $action->label(),
            ])->all(),
        ]);
    }

    public function updateLock(
        UpdateScoreLockRequest $request,
        PanelSubmissionAssignment $panelSubmissionAssignment,
        GovernanceRecorder $governance,
    ): RedirectResponse {
        $this->authorize('update', $panelSubmissionAssignment->panel);

        $intent = $request->validated('intent');
        $reason = $request->validated('reason');

        if ($intent === 'lock') {
            ScoreLock::query()->create([
                'panel_submission_assignment_id' => $panelSubmissionAssignment->id,
                'locked_by' => $request->user()?->id,
                'locked_at' => now(),
                'reason' => $reason,
            ]);

            $panelSubmissionAssignment->update(['status' => PanelSubmissionAssignmentStatus::Locked]);
            $panelSubmissionAssignment->scoreEntries()->update(['is_locked' => true]);
        } else {
            $panelSubmissionAssignment->currentLock?->update([
                'reopened_by' => $request->user()?->id,
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ]);

            $panelSubmissionAssignment->update(['status' => PanelSubmissionAssignmentStatus::Scoring]);
            $panelSubmissionAssignment->scoreEntries()->update(['is_locked' => false]);
        }

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.panel-scoring.lock-updated',
            description: ucfirst($intent).'ed scoring state for '.$panelSubmissionAssignment->submission?->title.'.',
            subject: $panelSubmissionAssignment,
            request: $request,
        );

        $governance->record(
            eventType: OverrideEventType::ScoreLockUpdated,
            actor: $request->user(),
            reason: $reason,
            submission: $panelSubmissionAssignment->submission,
            panelSubmissionAssignment: $panelSubmissionAssignment,
            beforeState: ['intent' => $intent === 'lock' ? 'open' : 'locked'],
            afterState: ['intent' => $intent],
        );

        return back()->with('success', 'Score lock state updated successfully.');
    }

    public function storeVisibilityEvent(
        ToggleScoreVisibilityRequest $request,
        PanelSubmissionAssignment $panelSubmissionAssignment,
        GovernanceRecorder $governance,
    ): RedirectResponse {
        $this->authorize('update', $panelSubmissionAssignment->panel);

        ScoreVisibilityEvent::query()->create([
            'panel_submission_assignment_id' => $panelSubmissionAssignment->id,
            'action' => $request->validated('action'),
            'note' => $request->validated('note'),
            'changed_by' => $request->user()?->id,
            'changed_at' => now(),
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.panel-scoring.visibility-updated',
            description: 'Recorded a score visibility event.',
            subject: $panelSubmissionAssignment,
            request: $request,
        );

        $governance->record(
            eventType: OverrideEventType::ScoreVisibilityUpdated,
            actor: $request->user(),
            reason: $request->validated('note'),
            submission: $panelSubmissionAssignment->submission,
            panelSubmissionAssignment: $panelSubmissionAssignment,
            afterState: ['action' => $request->validated('action')],
        );

        return back()->with('success', 'Score visibility event recorded successfully.');
    }

    public function updateConflict(
        UpdateConflictDeclarationRequest $request,
        ConflictOfInterestDeclaration $conflictDeclaration,
        GovernanceRecorder $governance,
    ): RedirectResponse {
        $this->authorize('update', $conflictDeclaration);

        $conflictDeclaration->update([
            'status' => $request->validated('status'),
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.judge-conflicts.updated',
            description: 'Updated a conflict of interest declaration.',
            subject: $conflictDeclaration,
            properties: [
                'admin_note' => $request->validated('admin_note'),
            ],
        );

        $governance->record(
            eventType: OverrideEventType::ConflictDecision,
            actor: $request->user(),
            reason: $request->validated('admin_note'),
            submission: $conflictDeclaration->submission,
            afterState: ['status' => $conflictDeclaration->status->value],
        );

        return back()->with('success', 'Conflict declaration updated successfully.');
    }
}
