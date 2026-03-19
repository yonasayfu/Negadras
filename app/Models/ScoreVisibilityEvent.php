<?php

namespace App\Models;

use App\ScoreVisibilityAction;
use Database\Factories\ScoreVisibilityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreVisibilityEvent extends Model
{
    /** @use HasFactory<ScoreVisibilityEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'panel_submission_assignment_id',
        'action',
        'note',
        'changed_by',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ScoreVisibilityAction::class,
            'changed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PanelSubmissionAssignment::class, 'panel_submission_assignment_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
