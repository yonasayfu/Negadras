<?php

namespace App\Models;

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

    public function isOwnedBy(User $user): bool
    {
        return $this->reviewer?->user_id === $user->id;
    }
}
