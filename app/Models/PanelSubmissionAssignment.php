<?php

namespace App\Models;

use App\PanelSubmissionAssignmentStatus;
use Database\Factories\PanelSubmissionAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PanelSubmissionAssignment extends Model
{
    /** @use HasFactory<PanelSubmissionAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'panel_id',
        'submission_id',
        'session_id_optional',
        'assigned_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'status' => PanelSubmissionAssignmentStatus::class,
        ];
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function scoreEntries(): HasMany
    {
        return $this->hasMany(ScoreEntry::class);
    }

    public function judgeComments(): HasMany
    {
        return $this->hasMany(JudgeComment::class);
    }

    public function scoreLocks(): HasMany
    {
        return $this->hasMany(ScoreLock::class)->latest('locked_at');
    }

    public function visibilityEvents(): HasMany
    {
        return $this->hasMany(ScoreVisibilityEvent::class)->latest('changed_at');
    }

    public function currentLock(): HasOne
    {
        return $this->hasOne(ScoreLock::class)->latestOfMany('locked_at');
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class, 'session_id_optional');
    }
}
