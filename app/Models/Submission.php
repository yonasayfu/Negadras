<?php

namespace App\Models;

use App\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'current_stage_id',
        'industry_id',
        'applicant_id',
        'organization_id',
        'title',
        'summary',
        'problem_statement',
        'solution_description',
        'business_model',
        'status',
        'submitted_at',
        'is_public_after_approval',
        'current_version_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'is_public_after_approval' => 'boolean',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'current_stage_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SubmissionVersion::class)->latest('version_no');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Draft);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Eligible);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->applicant->user_id === $user->id;
    }

    public function isEditableByPresenter(): bool
    {
        return $this->status->allowsPresenterEdits();
    }
}
