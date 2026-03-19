<?php

namespace App\Models;

use Database\Factories\SubmissionVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionVersion extends Model
{
    /** @use HasFactory<SubmissionVersionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'version_no',
        'snapshot_json',
        'change_note',
        'created_by',
        'is_locked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'is_locked' => 'boolean',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
