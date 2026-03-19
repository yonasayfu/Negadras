<?php

namespace App\Models;

use App\OverrideEventType;
use Database\Factories\OverrideEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverrideEvent extends Model
{
    /** @use HasFactory<OverrideEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'submission_id',
        'competition_session_id',
        'panel_submission_assignment_id',
        'reviewer_assignment_id',
        'event_type',
        'reason',
        'before_state',
        'after_state',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => OverrideEventType::class,
            'before_state' => 'array',
            'after_state' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }

    public function panelSubmissionAssignment(): BelongsTo
    {
        return $this->belongsTo(PanelSubmissionAssignment::class);
    }

    public function reviewerAssignment(): BelongsTo
    {
        return $this->belongsTo(ReviewerAssignment::class);
    }
}
