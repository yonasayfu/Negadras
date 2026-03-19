<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShortlistRecordRequest;
use App\Http\Requests\Admin\UpdateShortlistRecordRequest;
use App\Models\ShortlistRecord;
use App\Models\Stage;
use App\Models\Submission;
use App\ShortlistApprovalStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShortlistController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', ShortlistRecord::class);

        $search = $request->string('search')->trim()->toString();
        $approvalStatus = $request->string('approval_status')->trim()->toString();
        $stageId = $request->integer('stage_id');

        return Inertia::render('admin/Shortlist/Index', [
            'records' => ShortlistRecord::query()
                ->with([
                    'submission.season:id,name',
                    'submission.applicant:id,full_name,email',
                    'submission.organization:id,display_name',
                    'stage:id,name',
                    'creator:id,name',
                    'approver:id,name',
                ])
                ->when($approvalStatus !== '', fn ($query) => $query->where('approval_status', $approvalStatus))
                ->when($stageId > 0, fn ($query) => $query->where('stage_id', $stageId))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->whereHas('submission', function ($submissionQuery) use ($search): void {
                        $submissionQuery
                            ->where('title', 'ilike', "%{$search}%")
                            ->orWhereHas('applicant', fn ($applicantQuery) => $applicantQuery->where('full_name', 'ilike', "%{$search}%"))
                            ->orWhereHas('organization', fn ($organizationQuery) => $organizationQuery->where('display_name', 'ilike', "%{$search}%"));
                    });
                })
                ->latest()
                ->get()
                ->map(fn (ShortlistRecord $record): array => $this->recordSummary($record))
                ->all(),
            'filters' => [
                'search' => $search,
                'approvalStatus' => $approvalStatus,
                'stageId' => $stageId > 0 ? (string) $stageId : '',
            ],
            'approvalStatusOptions' => collect(ShortlistApprovalStatus::cases())->map(fn (ShortlistApprovalStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
            'stageOptions' => Stage::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Stage $stage): array => [
                    'value' => (string) $stage->id,
                    'label' => $stage->name,
                ])
                ->all(),
        ]);
    }

    public function store(StoreShortlistRecordRequest $request, Submission $submission): RedirectResponse
    {
        $this->authorize('create', ShortlistRecord::class);

        ShortlistRecord::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
            ],
            [
                'rank_order_optional' => $request->validated('rank_order_optional'),
                'notes' => $request->validated('notes'),
                'created_by' => $request->user()->id,
                'approval_status' => ShortlistApprovalStatus::Pending,
            ],
        );

        return back()->with('success', 'Shortlist record saved successfully.');
    }

    public function update(UpdateShortlistRecordRequest $request, ShortlistRecord $shortlistRecord): RedirectResponse
    {
        $this->authorize('update', $shortlistRecord);

        $approvalStatus = ShortlistApprovalStatus::from($request->validated('approval_status'));

        $shortlistRecord->update([
            'rank_order_optional' => $request->validated('rank_order_optional'),
            'notes' => $request->validated('notes'),
            'approval_status' => $approvalStatus,
            'approved_by' => $approvalStatus === ShortlistApprovalStatus::Approved ? $request->user()->id : null,
            'approved_at' => $approvalStatus === ShortlistApprovalStatus::Approved ? now() : null,
        ]);

        return back()->with('success', 'Shortlist record updated successfully.');
    }

    public function export()
    {
        $this->authorize('viewAny', ShortlistRecord::class);

        $records = ShortlistRecord::query()
            ->with(['submission.applicant:id,full_name', 'submission.organization:id,display_name', 'stage:id,name'])
            ->where('approval_status', ShortlistApprovalStatus::Approved)
            ->orderBy('stage_id')
            ->orderBy('rank_order_optional')
            ->get();

        if ($records->isNotEmpty()) {
            ShortlistRecord::query()
                ->whereIn('id', $records->pluck('id'))
                ->update(['exported_at' => now()]);
        }

        $csv = collect([
            ['Submission', 'Presenter', 'Organization', 'Stage', 'Rank', 'Approval status', 'Exported at'],
            ...$records->map(fn (ShortlistRecord $record): array => [
                $record->submission?->title ?? '',
                $record->submission?->applicant?->full_name ?? '',
                $record->submission?->organization?->display_name ?? '',
                $record->stage?->name ?? '',
                (string) ($record->rank_order_optional ?? ''),
                $record->approval_status->label(),
                now()->toDateTimeString(),
            ]),
        ])->map(fn (array $row): string => implode(',', array_map(fn (string $value): string => '"'.str_replace('"', '""', $value).'"', $row)))->implode("\n");

        return Response::streamDownload(
            fn (): int|false => print ($csv),
            'negadras-shortlist.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function recordSummary(ShortlistRecord $record): array
    {
        return [
            'id' => $record->id,
            'submissionId' => $record->submission_id,
            'submissionTitle' => $record->submission?->title,
            'seasonName' => $record->submission?->season?->name,
            'stageName' => $record->stage?->name,
            'applicantName' => $record->submission?->applicant?->full_name,
            'organizationName' => $record->submission?->organization?->display_name,
            'rankOrderOptional' => $record->rank_order_optional,
            'notes' => $record->notes,
            'approvalStatus' => $record->approval_status->value,
            'approvalStatusLabel' => $record->approval_status->label(),
            'createdBy' => $record->creator?->name,
            'approvedBy' => $record->approver?->name,
            'approvedAt' => $record->approved_at?->toDateTimeString(),
            'exportedAt' => $record->exported_at?->toDateTimeString(),
            'createdAt' => $record->created_at?->toDateTimeString(),
        ];
    }
}
