<?php

namespace App\Models;

use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use Database\Factories\ReviewerAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewerAssignment extends Model
{
    /** @use HasFactory<ReviewerAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'stage_id',
        'assignment_type',
        'assigned_at',
        'due_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'due_at' => 'datetime',
            'assignment_type' => ReviewAssignmentType::class,
            'status' => ReviewerAssignmentStatus::class,
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Reviewer::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function screeningReview(): HasOne
    {
        return $this->hasOne(ScreeningReview::class);
    }

    public function technicalReview(): HasOne
    {
        return $this->hasOne(TechnicalReview::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->reviewer?->user_id === $user->id;
    }

    public function isScreening(): bool
    {
        return $this->assignment_type === ReviewAssignmentType::Screening;
    }

    public function isTechnical(): bool
    {
        return $this->assignment_type === ReviewAssignmentType::Technical;
    }
}
