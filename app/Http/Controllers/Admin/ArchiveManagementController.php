<?php

namespace App\Http\Controllers\Admin;

use App\ArchiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArchiveRecordRequest;
use App\Http\Requests\Admin\StoreSessionHighlightRequest;
use App\Http\Requests\Admin\UpdateArchiveRecordRequest;
use App\Http\Requests\Admin\UpdateSessionHighlightRequest;
use App\Models\ArchiveRecord;
use App\Models\CompetitionSession;
use App\Models\Media;
use App\Models\PublicShowcaseEntry;
use App\Models\RankingSnapshot;
use App\Models\SessionHighlight;
use App\Models\Submission;
use App\PublicVisibilityStatus;
use App\Support\ArchivePublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveManagementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ArchiveRecord::class);

        return Inertia::render('admin/Archive/Index', [
            'records' => ArchiveRecord::query()
                ->with([
                    'submission.applicant:id,full_name',
                    'submission.organization:id,display_name',
                    'season:id,name',
                    'stage:id,name',
                    'competitionSession:id,name',
                    'showcaseEntry',
                ])
                ->latest('updated_at')
                ->get()
                ->map(fn (ArchiveRecord $record): array => [
                    'id' => $record->id,
                    'submissionId' => $record->submission_id,
                    'submissionTitle' => $record->submission?->title,
                    'applicantName' => $record->submission?->applicant?->full_name,
                    'organizationName' => $record->submission?->organization?->display_name,
                    'seasonName' => $record->season?->name,
                    'stageName' => $record->stage?->name,
                    'sessionName' => $record->competitionSession?->name,
                    'archiveStatus' => $record->archive_status->value,
                    'archiveStatusLabel' => $record->archive_status->label(),
                    'archiveStatusTone' => $record->archive_status->tone(),
                    'publicVisibility' => $record->public_visibility->value,
                    'publicVisibilityLabel' => $record->public_visibility->label(),
                    'archivedAt' => $record->archived_at?->toDateTimeString(),
                    'notes' => $record->notes,
                    'showcase' => $record->showcaseEntry === null ? null : [
                        'title' => $record->showcaseEntry->title,
                        'subtitle' => $record->showcaseEntry->subtitle,
                        'summary' => $record->showcaseEntry->summary,
                        'winnerLabel' => $record->showcaseEntry->winner_label,
                    ],
                ])
                ->all(),
            'rankingOptions' => RankingSnapshot::query()
                ->with(['submission.applicant:id,full_name'])
                ->whereNotNull('finalized_at')
                ->orderBy('rank_position')
                ->get()
                ->map(fn (RankingSnapshot $snapshot): array => [
                    'value' => $snapshot->id,
                    'submissionId' => $snapshot->submission_id,
                    'competitionSessionId' => $snapshot->competition_session_id,
                    'label' => "{$snapshot->rank_position}. {$snapshot->submission?->title} · ".($snapshot->submission?->applicant?->full_name ?? 'Unknown presenter'),
                ])
                ->all(),
            'sessionOptions' => CompetitionSession::query()->orderByDesc('scheduled_at')->get(['id', 'name'])->map(fn (CompetitionSession $session): array => [
                'value' => $session->id,
                'label' => $session->name,
            ])->all(),
            'mediaOptions' => Media::query()->latest()->limit(50)->get(['id', 'file_name'])->map(fn (Media $media): array => [
                'value' => $media->id,
                'label' => $media->file_name,
            ])->all(),
            'highlights' => SessionHighlight::query()
                ->with('competitionSession:id,name')
                ->orderBy('display_order')
                ->get()
                ->map(fn (SessionHighlight $highlight): array => [
                    'id' => $highlight->id,
                    'competitionSessionId' => $highlight->competition_session_id,
                    'competitionSessionName' => $highlight->competitionSession?->name,
                    'title' => $highlight->title,
                    'summary' => $highlight->summary,
                    'quoteOptional' => $highlight->quote_optional,
                    'quoteSourceOptional' => $highlight->quote_source_optional,
                    'displayOrder' => $highlight->display_order,
                    'isPublic' => $highlight->is_public,
                ])
                ->all(),
            'archiveStatusOptions' => collect(ArchiveStatus::cases())->map(fn (ArchiveStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
            'publicVisibilityOptions' => collect(PublicVisibilityStatus::cases())->map(fn (PublicVisibilityStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
        ]);
    }

    public function store(
        StoreArchiveRecordRequest $request,
        ArchivePublisher $publisher,
    ): RedirectResponse {
        $this->authorize('create', ArchiveRecord::class);

        $submission = Submission::query()->findOrFail($request->integer('submission_id'));
        $rankingSnapshot = $request->integer('ranking_snapshot_id') > 0
            ? RankingSnapshot::query()->findOrFail($request->integer('ranking_snapshot_id'))
            : null;
        $competitionSession = $request->integer('competition_session_id') > 0
            ? CompetitionSession::query()->findOrFail($request->integer('competition_session_id'))
            : null;
        $archiveStatus = ArchiveStatus::from($request->string('archive_status')->toString());
        $publicVisibility = PublicVisibilityStatus::from($request->string('public_visibility')->toString());

        $archiveRecord = $publisher->publish(
            $submission,
            $rankingSnapshot,
            $competitionSession,
            $request->user(),
            $archiveStatus,
            $publicVisibility,
            $request->string('notes')->toString() ?: null,
        );

        $this->syncShowcaseEntry($archiveRecord, $request);

        return back()->with('success', 'Archive record generated successfully.');
    }

    public function update(UpdateArchiveRecordRequest $request, ArchiveRecord $archiveRecord): RedirectResponse
    {
        $this->authorize('update', $archiveRecord);

        $archiveRecord->update([
            'archive_status' => $request->string('archive_status')->toString(),
            'public_visibility' => $request->string('public_visibility')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        $this->syncShowcaseEntry($archiveRecord, $request);

        return back()->with('success', 'Archive record updated successfully.');
    }

    public function storeHighlight(StoreSessionHighlightRequest $request): RedirectResponse
    {
        $this->authorize('create', ArchiveRecord::class);

        SessionHighlight::query()->create($request->validated());

        return back()->with('success', 'Session highlight saved successfully.');
    }

    public function updateHighlight(UpdateSessionHighlightRequest $request, SessionHighlight $sessionHighlight): RedirectResponse
    {
        $this->authorize('update', ArchiveRecord::class);

        $sessionHighlight->update($request->validated());

        return back()->with('success', 'Session highlight updated successfully.');
    }

    private function syncShowcaseEntry(ArchiveRecord $archiveRecord, Request $request): void
    {
        if (
            blank($request->input('showcase_title'))
            && blank($request->input('showcase_summary'))
            && $request->input('public_visibility') !== PublicVisibilityStatus::Public->value
        ) {
            return;
        }

        PublicShowcaseEntry::query()->updateOrCreate(
            [
                'archive_record_id' => $archiveRecord->id,
            ],
            [
                'season_id' => $archiveRecord->season_id,
                'industry_id' => $archiveRecord->submission?->industry_id,
                'media_id_optional' => $request->integer('showcase_media_id_optional') > 0 ? $request->integer('showcase_media_id_optional') : null,
                'slug' => $archiveRecord->showcaseEntry?->slug ?? Str::slug($request->string('showcase_title')->toString() ?: $archiveRecord->submission?->title ?: 'showcase-entry-'.$archiveRecord->id),
                'title' => $request->string('showcase_title')->toString() ?: $archiveRecord->submission?->title ?: 'Showcase entry',
                'subtitle' => $request->string('showcase_subtitle')->toString() ?: null,
                'visibility_status' => $request->string('public_visibility')->toString(),
                'summary' => $request->string('showcase_summary')->toString() ?: $archiveRecord->notes ?: 'Archive entry summary pending.',
                'winner_label' => $request->string('showcase_winner_label')->toString() ?: null,
            ],
        );
    }
}
