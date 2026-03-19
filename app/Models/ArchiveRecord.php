<?php

namespace App\Models;

use App\ArchiveStatus;
use App\PublicVisibilityStatus;
use Database\Factories\ArchiveRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArchiveRecord extends Model
{
    /** @use HasFactory<ArchiveRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'season_id',
        'stage_id',
        'competition_session_id',
        'ranking_snapshot_id',
        'archived_at',
        'archive_status',
        'public_visibility',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'archive_status' => ArchiveStatus::class,
            'public_visibility' => PublicVisibilityStatus::class,
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }

    public function rankingSnapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class);
    }

    public function showcaseEntry(): HasOne
    {
        return $this->hasOne(PublicShowcaseEntry::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(AwardRecord::class, 'submission_id', 'submission_id');
    }
}
