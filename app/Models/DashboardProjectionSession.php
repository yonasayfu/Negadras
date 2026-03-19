<?php

namespace App\Models;

use App\DashboardProjectionStatus;
use Database\Factories\DashboardProjectionSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardProjectionSession extends Model
{
    /** @use HasFactory<DashboardProjectionSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'competition_session_id',
        'status',
        'source_label',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'started_at',
        'ended_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DashboardProjectionStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
