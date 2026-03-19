<?php

namespace App\Models;

use App\FeedbackVisibilityStatus;
use Database\Factories\PresenterFeedbackPacketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenterFeedbackPacket extends Model
{
    /** @use HasFactory<PresenterFeedbackPacketFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'stage_id',
        'summary',
        'strengths',
        'improvement_areas',
        'next_step_guidance',
        'generated_by',
        'visibility_status',
        'score_summary_optional',
        'sent_at_optional',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility_status' => FeedbackVisibilityStatus::class,
            'score_summary_optional' => 'decimal:2',
            'sent_at_optional' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
