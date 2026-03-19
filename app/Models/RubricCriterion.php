<?php

namespace App\Models;

use Database\Factories\RubricCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    /** @use HasFactory<RubricCriterionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rubric_id',
        'name',
        'description',
        'max_score',
        'weight',
        'order_index',
        'is_required',
        'visibility_rule',
        'help_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function scoreEntries(): HasMany
    {
        return $this->hasMany(ScoreEntry::class);
    }
}
