<?php

namespace App\Models;

use App\ShortlistApprovalStatus;
use Database\Factories\ShortlistRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortlistRecord extends Model
{
    /** @use HasFactory<ShortlistRecordFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'stage_id',
        'rank_order_optional',
        'notes',
        'created_by',
        'approval_status',
        'approved_by',
        'approved_at',
        'exported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_status' => ShortlistApprovalStatus::class,
            'approved_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
