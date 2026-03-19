<?php

namespace App\Models;

use Database\Factories\SessionHighlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionHighlight extends Model
{
    /** @use HasFactory<SessionHighlightFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'competition_session_id',
        'title',
        'summary',
        'quote_optional',
        'quote_source_optional',
        'display_order',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function competitionSession(): BelongsTo
    {
        return $this->belongsTo(CompetitionSession::class);
    }
}
