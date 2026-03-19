<?php

namespace App\Models;

use Database\Factories\RankingSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingSnapshot extends Model
{
    /** @use HasFactory<RankingSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'stage_id',
        'competition_session_id',
        'submission_id',
        'aggregate_score',
        'rank_position',
        'tie_break_reason_optional',
        'override_reason_optional',
        'overridden_by',
        'finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aggregate_score' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function overrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
