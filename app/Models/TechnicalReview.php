<?php

namespace App\Models;

use App\TechnicalReviewRecommendation;
use Database\Factories\TechnicalReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalReview extends Model
{
    /** @use HasFactory<TechnicalReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'reviewer_assignment_id',
        'reviewer_id',
        'stage_id',
        'innovation_score_optional',
        'feasibility_score_optional',
        'execution_score_optional',
        'market_score_optional',
        'strengths',
        'weaknesses',
        'risk_note',
        'recommendation',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommendation' => TechnicalReviewRecommendation::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewerAssignment(): BelongsTo
    {
        return $this->belongsTo(ReviewerAssignment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Reviewer::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
