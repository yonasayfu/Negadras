<?php

namespace App\Models;

use App\ConflictOfInterestStatus;
use App\ConflictOfInterestType;
use Database\Factories\ConflictOfInterestDeclarationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictOfInterestDeclaration extends Model
{
    /** @use HasFactory<ConflictOfInterestDeclarationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judge_id',
        'submission_id',
        'session_id_optional',
        'conflict_type',
        'description',
        'declared_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conflict_type' => ConflictOfInterestType::class,
            'status' => ConflictOfInterestStatus::class,
            'declared_at' => 'datetime',
        ];
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
