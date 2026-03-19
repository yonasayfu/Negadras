<?php

namespace App\Models;

use Database\Factories\ScoreEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreEntry extends Model
{
    /** @use HasFactory<ScoreEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'panel_submission_assignment_id',
        'session_id_optional',
        'panel_id_optional',
        'judge_id',
        'rubric_criterion_id',
        'score_value',
        'comment',
        'is_secret',
        'is_locked',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_value' => 'decimal:2',
            'is_secret' => 'boolean',
            'is_locked' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PanelSubmissionAssignment::class, 'panel_submission_assignment_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }
}
