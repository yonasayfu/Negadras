<?php

namespace App\Http\Controllers;

use App\Models\PublicShowcaseEntry;
use App\PublicVisibilityStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicShowcaseController extends Controller
{
    public function index(Request $request): Response
    {
        $seasonId = $request->integer('season_id');
        $industryId = $request->integer('industry_id');
        $winnerType = $request->string('winner_type')->toString();

        $entries = PublicShowcaseEntry::query()
            ->with(['season:id,name', 'industry:id,name', 'archiveRecord.submission.applicant:id,full_name'])
            ->where('visibility_status', PublicVisibilityStatus::Public)
            ->when($seasonId > 0, fn ($query) => $query->where('season_id', $seasonId))
            ->when($industryId > 0, fn ($query) => $query->where('industry_id', $industryId))
            ->when($winnerType !== '', fn ($query) => $query->where('winner_label', $winnerType))
            ->latest()
            ->get();

        return Inertia::render('public/Showcase/Index', [
            'entries' => $entries->map(fn (PublicShowcaseEntry $entry): array => [
                'slug' => $entry->slug,
                'title' => $entry->title,
                'subtitle' => $entry->subtitle,
                'summary' => $entry->summary,
                'winnerLabel' => $entry->winner_label,
                'seasonName' => $entry->season?->name,
                'industryName' => $entry->industry?->name,
                'presenterName' => $entry->archiveRecord?->submission?->applicant?->full_name,
            ])->all(),
        ]);
    }

    public function show(PublicShowcaseEntry $publicShowcaseEntry): Response
    {
        abort_unless($publicShowcaseEntry->visibility_status === PublicVisibilityStatus::Public, 404);

        $publicShowcaseEntry->load([
            'season:id,name',
            'industry:id,name',
            'archiveRecord.submission.applicant:id,full_name',
            'archiveRecord.submission.organization:id,display_name',
            'archiveRecord.competitionSession.highlights',
            'archiveRecord.awards',
        ]);

        return Inertia::render('public/Showcase/Show', [
            'entry' => [
                'title' => $publicShowcaseEntry->title,
                'subtitle' => $publicShowcaseEntry->subtitle,
                'summary' => $publicShowcaseEntry->summary,
                'winnerLabel' => $publicShowcaseEntry->winner_label,
                'seasonName' => $publicShowcaseEntry->season?->name,
                'industryName' => $publicShowcaseEntry->industry?->name,
                'presenterName' => $publicShowcaseEntry->archiveRecord?->submission?->applicant?->full_name,
                'organizationName' => $publicShowcaseEntry->archiveRecord?->submission?->organization?->display_name,
                'awards' => $publicShowcaseEntry->archiveRecord?->awards->map(fn ($award): array => [
                    'label' => $award->award_type->label(),
                    'notes' => $award->notes,
                ])->values()->all() ?? [],
                'highlights' => $publicShowcaseEntry->archiveRecord?->competitionSession?->highlights
                    ?->where('is_public', true)
                    ->map(fn ($highlight): array => [
                        'title' => $highlight->title,
                        'summary' => $highlight->summary,
                        'quoteOptional' => $highlight->quote_optional,
                        'quoteSourceOptional' => $highlight->quote_source_optional,
                    ])->values()->all() ?? [],
            ],
        ]);
    }
}
