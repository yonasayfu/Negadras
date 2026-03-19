<?php

namespace App\Models;

use Database\Factories\LiveStatusSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStatusSnapshot extends Model
{
    /** @use HasFactory<LiveStatusSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'competition_session_id',
        'current_session_presenter_id',
        'status_payload',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_payload' => 'array',
        ];
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }

    public function currentPresenter(): BelongsTo
    {
        return $this->belongsTo(SessionPresenter::class, 'current_session_presenter_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
