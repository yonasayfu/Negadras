<?php

namespace App\Models;

use Database\Factories\SessionMediumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMedium extends Model
{
    /** @use HasFactory<SessionMediumFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'competition_session_id',
        'media_id',
        'usage_type',
        'display_order',
    ];

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
