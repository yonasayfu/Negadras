<?php

namespace App\Models;

use App\CompetitionSessionStatus;
use App\CompetitionSessionType;
use Database\Factories\CompetitionSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompetitionSession extends Model
{
    /** @use HasFactory<CompetitionSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'season_id',
        'stage_id',
        'panel_id',
        'name',
        'session_type',
        'scheduled_at',
        'broadcasted_at',
        'location',
        'status',
        'etv_video_url_optional',
        'started_at',
        'paused_at',
        'completed_at',
        'scores_revealed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_type' => CompetitionSessionType::class,
            'status' => CompetitionSessionStatus::class,
            'scheduled_at' => 'datetime',
            'broadcasted_at' => 'datetime',
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
            'scores_revealed' => 'boolean',
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

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function presenters(): HasMany
    {
        return $this->hasMany(SessionPresenter::class)->orderBy('order_index');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SessionEvent::class)->latest('created_at');
    }

    public function projectionRequests(): HasMany
    {
        return $this->hasMany(DashboardProjectionSession::class)->latest('requested_at');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(LiveStatusSnapshot::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SessionMedium::class)->orderBy('display_order');
    }
}
