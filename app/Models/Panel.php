<?php

namespace App\Models;

use App\PanelStatus;
use App\PanelSubmissionAssignmentStatus;
use Database\Factories\PanelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panel extends Model
{
    /** @use HasFactory<PanelFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'stage_id',
        'rubric_id',
        'name',
        'description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PanelStatus::class,
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(PanelMember::class)->orderBy('display_order');
    }

    public function submissionAssignments(): HasMany
    {
        return $this->hasMany(PanelSubmissionAssignment::class)->latest('assigned_at');
    }

    public function activeSubmissionAssignments(): HasMany
    {
        return $this->submissionAssignments()->whereNot('status', PanelSubmissionAssignmentStatus::Locked);
    }
}
