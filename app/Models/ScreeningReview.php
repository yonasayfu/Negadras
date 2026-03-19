<?php

namespace App\Models;

use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use Database\Factories\ScreeningReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningReview extends Model
{
    /** @use HasFactory<ScreeningReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'reviewer_assignment_id',
        'eligibility_status',
        'recommendation',
        'score_optional',
        'notes',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eligibility_status' => ScreeningEligibilityStatus::class,
            'recommendation' => ScreeningRecommendation::class,
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
