<?php

namespace App\Models;

use App\StageStatus;
use App\StageType;
use Database\Factories\StageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    /** @use HasFactory<StageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'name',
        'code',
        'type',
        'order_index',
        'starts_at',
        'ends_at',
        'status',
        'is_live_stage',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StageType::class,
            'status' => StageStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_live_stage' => 'boolean',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'current_stage_id')->latest();
    }
}
