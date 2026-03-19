<?php

namespace App\Models;

use App\PublicVisibilityStatus;
use Database\Factories\PublicShowcaseEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicShowcaseEntry extends Model
{
    /** @use HasFactory<PublicShowcaseEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'archive_record_id',
        'season_id',
        'industry_id',
        'media_id_optional',
        'slug',
        'title',
        'subtitle',
        'visibility_status',
        'summary',
        'winner_label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility_status' => PublicVisibilityStatus::class,
        ];
    }

    public function archiveRecord(): BelongsTo
    {
        return $this->belongsTo(ArchiveRecord::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id_optional');
    }
}
