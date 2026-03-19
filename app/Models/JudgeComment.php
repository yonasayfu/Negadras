<?php

namespace App\Models;

use App\JudgeCommentType;
use Database\Factories\JudgeCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeComment extends Model
{
    /** @use HasFactory<JudgeCommentFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'judge_id',
        'panel_submission_assignment_id',
        'session_id_optional',
        'comment_type',
        'content',
        'is_archived',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'comment_type' => JudgeCommentType::class,
            'is_archived' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PanelSubmissionAssignment::class, 'panel_submission_assignment_id');
    }
}
