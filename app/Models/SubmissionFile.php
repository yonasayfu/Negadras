<?php

namespace App\Models;

use App\Support\SubmissionFileRegistry;
use Database\Factories\SubmissionFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    /** @use HasFactory<SubmissionFileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'submission_version_id',
        'file_type',
        'disk',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
        'uploaded_at',
        'is_required',
        'is_verified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'is_required' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class, 'submission_version_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return SubmissionFileRegistry::definition($this->file_type)['label'] ?? $this->file_type;
    }
}
