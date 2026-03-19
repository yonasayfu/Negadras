<?php

namespace App\Models;

use App\AwardType;
use Database\Factories\AwardRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardRecord extends Model
{
    /** @use HasFactory<AwardRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'submission_id',
        'ranking_snapshot_id',
        'award_type',
        'rank_position',
        'prize_value_optional',
        'notes',
        'granted_by',
        'granted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'award_type' => AwardType::class,
            'prize_value_optional' => 'decimal:2',
            'granted_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function rankingSnapshot(): BelongsTo
    {
        return $this->belongsTo(RankingSnapshot::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
