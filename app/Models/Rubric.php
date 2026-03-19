<?php

namespace App\Models;

use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    /** @use HasFactory<RubricFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'total_weight',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('order_index');
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'rubric_stage_bindings')->withTimestamps();
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'rubric_industry_bindings')->withTimestamps();
    }

    public function panels(): HasMany
    {
        return $this->hasMany(Panel::class);
    }
}
