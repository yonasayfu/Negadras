<?php

namespace App\Support;

use App\ArchiveStatus;
use App\Models\ArchiveRecord;
use App\Models\CompetitionSession;
use App\Models\RankingSnapshot;
use App\Models\Submission;
use App\Models\User;
use App\PublicVisibilityStatus;

class ArchivePublisher
{
    public function publish(
        Submission $submission,
        ?RankingSnapshot $rankingSnapshot,
        ?CompetitionSession $competitionSession,
        User $actor,
        ArchiveStatus $archiveStatus,
        PublicVisibilityStatus $publicVisibility,
        ?string $notes = null,
    ): ArchiveRecord {
        return ArchiveRecord::query()->updateOrCreate(
            [
                'submission_id' => $submission->id,
                'stage_id' => $submission->current_stage_id,
            ],
            [
                'season_id' => $submission->season_id,
                'competition_session_id' => $competitionSession?->id,
                'ranking_snapshot_id' => $rankingSnapshot?->id,
                'archived_at' => now(),
                'archive_status' => $archiveStatus,
                'public_visibility' => $publicVisibility,
                'notes' => $notes,
            ],
        );
    }
}
