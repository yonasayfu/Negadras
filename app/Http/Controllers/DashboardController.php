<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\ImportRun;
use App\Models\Media;
use App\Models\Page;
use App\Models\Season;
use App\Models\Submission;
use App\Models\User;
use App\SeasonStatus;
use App\SubmissionStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $applicant = $user?->applicant;
        $currentSeason = $this->currentSeason();

        return Inertia::render('Dashboard', [
            'currentSeason' => $this->seasonSummary($currentSeason),
            'presenterPortal' => $this->presenterPortal($applicant, $currentSeason),
            'operations' => [
                'metrics' => [
                    [
                        'key' => 'submissions',
                        'label' => 'Total submissions',
                        'value' => Submission::query()->count(),
                        'description' => 'All Negadras submission records currently tracked in the platform.',
                        'tone' => 'amber',
                    ],
                    [
                        'key' => 'returned',
                        'label' => 'Returned for correction',
                        'value' => Submission::query()->where('status', SubmissionStatus::IncompleteReturned)->count(),
                        'description' => 'Submissions currently waiting on presenter corrections.',
                        'tone' => 'sky',
                    ],
                    [
                        'key' => 'eligible',
                        'label' => 'Eligible',
                        'value' => Submission::query()->where('status', SubmissionStatus::Eligible)->count(),
                        'description' => 'Records that have passed intake and are ready for later workflow phases.',
                        'tone' => 'emerald',
                    ],
                    [
                        'key' => 'activeSeason',
                        'label' => 'Active season',
                        'value' => Season::query()->where('status', SeasonStatus::Active)->count(),
                        'description' => 'Seasons currently marked active for Negadras operations.',
                        'tone' => 'violet',
                    ],
                ],
                'submissionBreakdown' => [
                    [
                        'label' => 'Draft',
                        'value' => Submission::query()->where('status', SubmissionStatus::Draft)->count(),
                    ],
                    [
                        'label' => 'Submitted',
                        'value' => Submission::query()->where('status', SubmissionStatus::Submitted)->count(),
                    ],
                    [
                        'label' => 'Under intake check',
                        'value' => Submission::query()->where('status', SubmissionStatus::UnderIntakeCheck)->count(),
                    ],
                    [
                        'label' => 'Returned',
                        'value' => Submission::query()->where('status', SubmissionStatus::IncompleteReturned)->count(),
                    ],
                    [
                        'label' => 'Eligible',
                        'value' => Submission::query()->where('status', SubmissionStatus::Eligible)->count(),
                    ],
                    [
                        'label' => 'Rejected',
                        'value' => Submission::query()->where('status', SubmissionStatus::Rejected)->count(),
                    ],
                ],
                'recentSubmissions' => Submission::query()
                    ->with(['applicant:id,full_name', 'season:id,name'])
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Submission $submission): array => [
                        'id' => $submission->id,
                        'title' => $submission->title,
                        'presenterName' => $submission->applicant?->full_name,
                        'seasonName' => $submission->season?->name,
                        'statusLabel' => $submission->status->label(),
                        'statusTone' => $submission->status->tone(),
                        'updatedAt' => $submission->updated_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ],
            'recentActivity' => ActivityLog::query()
                ->latest('created_at')
                ->limit(6)
                ->get()
                ->map(fn (ActivityLog $log): array => [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'createdAt' => $log->created_at?->toDateTimeString(),
                ])
                ->values()
                ->all(),
            'platformHealth' => [
                [
                    'key' => 'pages',
                    'label' => 'Pages',
                    'value' => Page::withTrashed()->count(),
                    'description' => 'Public content records including archived or deleted items.',
                    'tone' => 'amber',
                ],
                [
                    'key' => 'media',
                    'label' => 'Media files',
                    'value' => Media::query()->count(),
                    'description' => 'Shared business media ready for later attachment reuse.',
                    'tone' => 'sky',
                ],
                [
                    'key' => 'imports',
                    'label' => 'Import runs',
                    'value' => ImportRun::query()->count(),
                    'description' => 'Previewed and completed bulk-intake operations.',
                    'tone' => 'emerald',
                ],
                [
                    'key' => 'activeUsers',
                    'label' => 'Active users',
                    'value' => User::query()->count(),
                    'description' => 'Signed-in operators and role-managed users in the workspace.',
                    'tone' => 'violet',
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presenterPortal(?Applicant $applicant, ?Season $currentSeason): ?array
    {
        if ($applicant === null) {
            return [
                'hasApplicantProfile' => false,
                'canCreateSubmission' => false,
                'counts' => [
                    'draft' => 0,
                    'submitted' => 0,
                    'returned' => 0,
                    'total' => 0,
                ],
                'recentSubmissions' => [],
            ];
        }

        $submissionQuery = Submission::query()
            ->where('applicant_id', $applicant->id);

        return [
            'hasApplicantProfile' => true,
            'canCreateSubmission' => $this->isSeasonOpenForApplications($currentSeason),
            'counts' => [
                'draft' => (clone $submissionQuery)->where('status', SubmissionStatus::Draft)->count(),
                'submitted' => (clone $submissionQuery)->whereIn('status', [
                    SubmissionStatus::Submitted,
                    SubmissionStatus::UnderIntakeCheck,
                    SubmissionStatus::Eligible,
                    SubmissionStatus::Rejected,
                ])->count(),
                'returned' => (clone $submissionQuery)->where('status', SubmissionStatus::IncompleteReturned)->count(),
                'total' => (clone $submissionQuery)->count(),
            ],
            'recentSubmissions' => (clone $submissionQuery)
                ->with(['season:id,name'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(fn (Submission $submission): array => [
                    'id' => $submission->id,
                    'title' => $submission->title,
                    'seasonName' => $submission->season?->name,
                    'statusLabel' => $submission->status->label(),
                    'statusTone' => $submission->status->tone(),
                    'updatedAt' => $submission->updated_at?->toDateTimeString(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function currentSeason(): ?Season
    {
        return Season::query()
            ->where('status', SeasonStatus::Active)
            ->orderByDesc('year')
            ->orderByDesc('registration_open_at')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function seasonSummary(?Season $season): ?array
    {
        if ($season === null) {
            return null;
        }

        $isOpenForApplications = $this->isSeasonOpenForApplications($season);
        $opensAt = $season->registration_open_at?->toDateTimeString();
        $closesAt = $season->registration_close_at?->toDateTimeString();

        $registrationLabel = 'Open for applications';

        if (! $isOpenForApplications && $season->registration_open_at !== null && $season->registration_open_at->isFuture()) {
            $registrationLabel = 'Applications open soon';
        } elseif (! $isOpenForApplications) {
            $registrationLabel = 'Applications currently closed';
        }

        return [
            'id' => $season->id,
            'name' => $season->name,
            'year' => $season->year,
            'description' => $season->description,
            'registrationOpenAt' => $opensAt,
            'registrationCloseAt' => $closesAt,
            'isOpenForApplications' => $isOpenForApplications,
            'registrationLabel' => $registrationLabel,
            'statusLabel' => $season->status->label(),
            'statusTone' => $season->status->tone(),
        ];
    }

    private function isSeasonOpenForApplications(?Season $season): bool
    {
        if ($season === null || $season->status !== SeasonStatus::Active) {
            return false;
        }

        $now = now();

        if ($season->registration_open_at !== null && $season->registration_open_at->isAfter($now)) {
            return false;
        }

        if ($season->registration_close_at !== null && $season->registration_close_at->isBefore($now)) {
            return false;
        }

        return true;
    }
}
