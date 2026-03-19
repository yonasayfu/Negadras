<?php

namespace App\Http\Controllers;

use App\ExportJobStatus;
use App\Models\ArchiveRecord;
use App\Models\AwardRecord;
use App\Models\ExportJob;
use App\Models\RankingSnapshot;
use App\Models\ShortlistRecord;
use App\Models\Submission;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCenterController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('exports/Index', [
            'resources' => [
                [
                    'key' => 'submissions-csv',
                    'title' => 'Submissions export',
                    'description' => 'Competition intake records with current season, stage, presenter, and status.',
                    'href' => route('exports.submissions.csv'),
                    'actionLabel' => 'Download CSV',
                    'format' => 'CSV',
                ],
                [
                    'key' => 'shortlist-csv',
                    'title' => 'Shortlist export',
                    'description' => 'Approved shortlist records for downstream board review and external sharing.',
                    'href' => route('exports.shortlist.csv'),
                    'actionLabel' => 'Download CSV',
                    'format' => 'CSV',
                ],
                [
                    'key' => 'rankings-csv',
                    'title' => 'Ranking export',
                    'description' => 'Ranking snapshots with aggregate score and rank positions.',
                    'href' => route('exports.rankings.csv'),
                    'actionLabel' => 'Download CSV',
                    'format' => 'CSV',
                ],
                [
                    'key' => 'awards-csv',
                    'title' => 'Awards export',
                    'description' => 'Award outcomes and linked submissions ready for ceremonies and reporting.',
                    'href' => route('exports.awards.csv'),
                    'actionLabel' => 'Download CSV',
                    'format' => 'CSV',
                ],
                [
                    'key' => 'archive-csv',
                    'title' => 'Archive export',
                    'description' => 'Archive publication records and public-visibility state.',
                    'href' => route('exports.archive.csv'),
                    'actionLabel' => 'Download CSV',
                    'format' => 'CSV',
                ],
            ],
            'recentJobs' => ExportJob::query()
                ->with('requester:id,name')
                ->latest('completed_at')
                ->limit(8)
                ->get()
                ->map(fn (ExportJob $job): array => [
                    'id' => $job->id,
                    'type' => $job->type,
                    'statusLabel' => $job->status->label(),
                    'statusTone' => $job->status->tone(),
                    'fileName' => $job->file_name,
                    'rowCount' => $job->row_count,
                    'requestedBy' => $job->requester?->name,
                    'completedAt' => $job->completed_at?->toDateTimeString(),
                ])
                ->all(),
        ]);
    }

    public function submissionsCsv(Request $request): StreamedResponse
    {
        $rows = Submission::query()
            ->with(['season:id,name', 'currentStage:id,name', 'applicant:id,full_name', 'organization:id,display_name'])
            ->orderByDesc('updated_at')
            ->get();

        $this->recordJob($request, 'submissions_csv', 'negadras.exports.submissions', $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Title', 'Season', 'Stage', 'Applicant', 'Organization', 'Status', 'Submitted At']);

            foreach ($rows as $submission) {
                fputcsv($handle, [
                    $submission->title,
                    $submission->season?->name,
                    $submission->currentStage?->name,
                    $submission->applicant?->full_name,
                    $submission->organization?->display_name,
                    $submission->status->value,
                    $submission->submitted_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'negadras-submissions.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function shortlistCsv(Request $request): StreamedResponse
    {
        $rows = ShortlistRecord::query()
            ->with(['submission.applicant:id,full_name', 'submission.organization:id,display_name', 'stage:id,name'])
            ->orderBy('rank_order_optional')
            ->get();

        $this->recordJob($request, 'shortlist_csv', 'negadras.exports.shortlist', $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Submission', 'Applicant', 'Organization', 'Stage', 'Rank', 'Approval Status']);

            foreach ($rows as $record) {
                fputcsv($handle, [
                    $record->submission?->title,
                    $record->submission?->applicant?->full_name,
                    $record->submission?->organization?->display_name,
                    $record->stage?->name,
                    $record->rank_order_optional,
                    $record->approval_status->value,
                ]);
            }

            fclose($handle);
        }, 'negadras-shortlist.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function rankingsCsv(Request $request): StreamedResponse
    {
        $rows = RankingSnapshot::query()
            ->with(['submission.applicant:id,full_name', 'stage:id,name', 'competitionSession:id,name'])
            ->orderBy('rank_position')
            ->get();

        $this->recordJob($request, 'rankings_csv', 'negadras.exports.rankings', $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Submission', 'Applicant', 'Stage', 'Session', 'Score', 'Rank']);

            foreach ($rows as $snapshot) {
                fputcsv($handle, [
                    $snapshot->submission?->title,
                    $snapshot->submission?->applicant?->full_name,
                    $snapshot->stage?->name,
                    $snapshot->competitionSession?->name,
                    $snapshot->aggregate_score,
                    $snapshot->rank_position,
                ]);
            }

            fclose($handle);
        }, 'negadras-rankings.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function awardsCsv(Request $request): StreamedResponse
    {
        $rows = AwardRecord::query()
            ->with(['submission.applicant:id,full_name', 'season:id,name'])
            ->latest('granted_at')
            ->get();

        $this->recordJob($request, 'awards_csv', 'negadras.exports.awards', $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Submission', 'Applicant', 'Season', 'Award Type', 'Rank', 'Granted At']);

            foreach ($rows as $award) {
                fputcsv($handle, [
                    $award->submission?->title,
                    $award->submission?->applicant?->full_name,
                    $award->season?->name,
                    $award->award_type->value,
                    $award->rank_position,
                    $award->granted_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'negadras-awards.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function archiveCsv(Request $request): StreamedResponse
    {
        $rows = ArchiveRecord::query()
            ->with(['submission.applicant:id,full_name', 'season:id,name', 'stage:id,name'])
            ->latest('archived_at')
            ->get();

        $this->recordJob($request, 'archive_csv', 'negadras.exports.archive', $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Submission', 'Applicant', 'Season', 'Stage', 'Archive Status', 'Visibility', 'Archived At']);

            foreach ($rows as $record) {
                fputcsv($handle, [
                    $record->submission?->title,
                    $record->submission?->applicant?->full_name,
                    $record->season?->name,
                    $record->stage?->name,
                    $record->archive_status->value,
                    $record->public_visibility->value,
                    $record->archived_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'negadras-archive.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function recordJob(Request $request, string $type, string $event, int $rowCount): void
    {
        ExportJob::query()->create([
            'type' => $type,
            'filters' => [],
            'requested_by' => $request->user()->id,
            'status' => ExportJobStatus::Completed,
            'file_name' => $type.'.csv',
            'row_count' => $rowCount,
            'completed_at' => now(),
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: $event,
            description: "Downloaded {$type} export.",
            properties: ['row_count' => $rowCount],
            request: $request,
        );
    }
}
