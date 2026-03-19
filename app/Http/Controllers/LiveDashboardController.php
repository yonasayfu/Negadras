<?php

namespace App\Http\Controllers;

use App\Models\CompetitionSession;
use App\Support\LiveSessionCoordinator;
use Inertia\Inertia;
use Inertia\Response;

class LiveDashboardController extends Controller
{
    public function show(CompetitionSession $competitionSession, LiveSessionCoordinator $coordinator): Response
    {
        $snapshot = $competitionSession->snapshot ?? $coordinator->refreshSnapshot($competitionSession);

        return Inertia::render('live-dashboard/Show', [
            'snapshot' => $snapshot->status_payload,
        ]);
    }
}
