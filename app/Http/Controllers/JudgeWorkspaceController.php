<?php

namespace App\Http\Controllers;

use App\ConflictOfInterestStatus;
use App\ConflictOfInterestType;
use App\Http\Requests\StoreConflictDeclarationRequest;
use App\Http\Requests\StoreJudgeScoreRequest;
use App\JudgeCommentType;
use App\Models\ConflictOfInterestDeclaration;
use App\Models\Judge;
use App\Models\PanelSubmissionAssignment;
use App\Support\ActivityLogger;
use App\Support\ScoreEngine;
use App\Support\SubmissionFileRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JudgeWorkspaceController extends Controller
{
    public function index(Request $request, ScoreEngine $scoreEngine): Response
    {
        $judge = $this->judgeOrAbort($request);

        return Inertia::render('judges/Index', [
            'assignments' => PanelSubmissionAssignment::query()
                ->with([
                    'panel.season:id,name',
                    'panel.stage:id,name',
                    'submission.applicant:id,full_name',
                    'submission.organization:id,display_name',
                ])
                ->whereHas('panel.members', fn ($query) => $query->where('judge_id', $judge->id))
                ->latest('assigned_at')
                ->get()
                ->map(fn (PanelSubmissionAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'panelName' => $assignment->panel?->name,
                    'seasonName' => $assignment->panel?->season?->name,
                    'stageName' => $assignment->panel?->stage?->name,
                    'title' => $assignment->submission?->title,
                    'applicantName' => $assignment->submission?->applicant?->full_name,
                    'organizationName' => $assignment->submission?->organization?->display_name,
                    'statusLabel' => $assignment->status->label(),
                    'statusTone' => $assignment->status->tone(),
                    'assignedAt' => $assignment->assigned_at?->toDateTimeString(),
                    'isLocked' => $scoreEngine->isLocked($assignment),
                    'hasActiveConflict' => $scoreEngine->hasActiveConflict($assignment, $judge),
                    'progress' => $scoreEngine->judgeProgress($assignment, $judge),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, PanelSubmissionAssignment $panelSubmissionAssignment, ScoreEngine $scoreEngine): Response
    {
        $judge = $this->judgeOrAbort($request);
        abort_unless($this->judgeOwnsAssignment($judge, $panelSubmissionAssignment), 403);

        $panelSubmissionAssignment->load([
            'panel.season:id,name',
            'panel.stage:id,name',
            'panel.rubric.criteria',
            'submission.applicant:id,full_name,email',
            'submission.organization:id,display_name',
            'submission.files.version:id,version_no',
            'submission.files.uploadedBy:id,name',
            'submission.conflictDeclarations.judge.user:id,name',
            'scoreEntries.criterion:id,name,max_score,weight',
            'judgeComments',
        ]);

        return Inertia::render('judges/Show', [
            'assignment' => [
                'id' => $panelSubmissionAssignment->id,
                'panelName' => $panelSubmissionAssignment->panel?->name,
                'seasonName' => $panelSubmissionAssignment->panel?->season?->name,
                'stageName' => $panelSubmissionAssignment->panel?->stage?->name,
                'title' => $panelSubmissionAssignment->submission?->title,
                'applicantName' => $panelSubmissionAssignment->submission?->applicant?->full_name,
                'organizationName' => $panelSubmissionAssignment->submission?->organization?->display_name,
                'status' => $panelSubmissionAssignment->status->value,
                'statusLabel' => $panelSubmissionAssignment->status->label(),
                'statusTone' => $panelSubmissionAssignment->status->tone(),
                'assignedAt' => $panelSubmissionAssignment->assigned_at?->toDateTimeString(),
                'isLocked' => $scoreEngine->isLocked($panelSubmissionAssignment),
                'hasActiveConflict' => $scoreEngine->hasActiveConflict($panelSubmissionAssignment, $judge),
                'progress' => $scoreEngine->judgeProgress($panelSubmissionAssignment, $judge),
                'rubric' => $panelSubmissionAssignment->panel?->rubric?->criteria
                    ->map(fn ($criterion): array => [
                        'id' => $criterion->id,
                        'name' => $criterion->name,
                        'description' => $criterion->description,
                        'maxScore' => (float) $criterion->max_score,
                        'weight' => (float) $criterion->weight,
                        'helpText' => $criterion->help_text,
                        'existingScore' => $panelSubmissionAssignment->scoreEntries
                            ->where('judge_id', $judge->id)
                            ->firstWhere('rubric_criterion_id', $criterion->id)?->score_value,
                        'existingComment' => $panelSubmissionAssignment->scoreEntries
                            ->where('judge_id', $judge->id)
                            ->firstWhere('rubric_criterion_id', $criterion->id)?->comment,
                    ])
                    ->values()
                    ->all() ?? [],
                'privateComment' => $panelSubmissionAssignment->judgeComments
                    ->where('judge_id', $judge->id)
                    ->firstWhere('comment_type', JudgeCommentType::Private)?->content,
                'presenterComment' => $panelSubmissionAssignment->judgeComments
                    ->where('judge_id', $judge->id)
                    ->firstWhere('comment_type', JudgeCommentType::PresenterVisible)?->content,
                'submissionFiles' => $panelSubmissionAssignment->submission?->files
                    ->map(fn ($file): array => [
                        'id' => $file->id,
                        'fileType' => $file->file_type,
                        'fileTypeLabel' => SubmissionFileRegistry::definition($file->file_type)['label'] ?? $file->file_type,
                        'originalName' => $file->original_name,
                        'downloadUrl' => route('submission-files.download', $file),
                        'uploadedAt' => $file->uploaded_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all() ?? [],
                'conflicts' => $panelSubmissionAssignment->submission?->conflictDeclarations
                    ->where('judge_id', $judge->id)
                    ->map(fn (ConflictOfInterestDeclaration $declaration): array => [
                        'id' => $declaration->id,
                        'typeLabel' => $declaration->conflict_type->label(),
                        'description' => $declaration->description,
                        'statusLabel' => $declaration->status->label(),
                        'declaredAt' => $declaration->declared_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all() ?? [],
                'aggregateScore' => $scoreEngine->aggregateTotal($panelSubmissionAssignment),
            ],
            'conflictTypeOptions' => collect(ConflictOfInterestType::cases())->map(fn ($type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->all(),
        ]);
    }

    public function storeScores(
        StoreJudgeScoreRequest $request,
        PanelSubmissionAssignment $panelSubmissionAssignment,
        ScoreEngine $scoreEngine,
    ): RedirectResponse {
        $judge = $this->judgeOrAbort($request);
        abort_unless($this->judgeOwnsAssignment($judge, $panelSubmissionAssignment), 403);

        $scoreEngine->saveScores(
            assignment: $panelSubmissionAssignment->loadMissing('submission', 'panel.rubric.criteria', 'panel.members', 'scoreEntries', 'judgeComments'),
            judge: $judge,
            scores: $request->validated('scores'),
            privateComment: $request->validated('private_comment'),
            presenterComment: $request->validated('presenter_comment'),
            submit: $request->validated('intent') === 'submit',
            actor: $request->user(),
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.judge-scores.saved',
            description: ucfirst($request->validated('intent'))."ed scores for {$panelSubmissionAssignment->submission?->title}.",
            subject: $panelSubmissionAssignment,
            request: $request,
        );

        return to_route('judge-workspace.show', $panelSubmissionAssignment)->with('success', 'Scores saved successfully.');
    }

    public function storeConflict(
        StoreConflictDeclarationRequest $request,
        PanelSubmissionAssignment $panelSubmissionAssignment,
    ): RedirectResponse {
        $judge = $this->judgeOrAbort($request);
        abort_unless($this->judgeOwnsAssignment($judge, $panelSubmissionAssignment), 403);

        ConflictOfInterestDeclaration::query()->updateOrCreate(
            [
                'judge_id' => $judge->id,
                'submission_id' => $panelSubmissionAssignment->submission_id,
                'status' => ConflictOfInterestStatus::Active,
            ],
            [
                'conflict_type' => $request->validated('conflict_type'),
                'description' => $request->validated('description'),
                'declared_at' => now(),
            ],
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.judge-conflicts.created',
            description: "Declared a conflict for {$panelSubmissionAssignment->submission?->title}.",
            subject: $panelSubmissionAssignment,
            request: $request,
        );

        return to_route('judge-workspace.show', $panelSubmissionAssignment)->with('success', 'Conflict declaration recorded successfully.');
    }

    private function judgeOrAbort(Request $request): Judge
    {
        $judge = $request->user()?->judge;

        abort_if($judge === null || ! $judge->is_active, 403);

        return $judge;
    }

    private function judgeOwnsAssignment(Judge $judge, PanelSubmissionAssignment $assignment): bool
    {
        return $assignment->panel()
            ->whereHas('members', fn ($query) => $query->where('judge_id', $judge->id))
            ->exists();
    }
}
