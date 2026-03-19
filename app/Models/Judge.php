<?php

namespace App\Models;

use Database\Factories\JudgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    /** @use HasFactory<JudgeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'professional_title',
        'organization',
        'specialization',
        'bio',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function panelMembers(): HasMany
    {
        return $this->hasMany(PanelMember::class)->orderBy('display_order');
    }

    public function panels(): BelongsToMany
    {
        return $this->belongsToMany(Panel::class, 'panel_members')
            ->withPivot(['role_in_panel', 'display_order'])
            ->withTimestamps();
    }

    public function scoreEntries(): HasMany
    {
        return $this->hasMany(ScoreEntry::class)->latest('submitted_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(JudgeComment::class)->latest('created_at');
    }

    public function conflictDeclarations(): HasMany
    {
        return $this->hasMany(ConflictOfInterestDeclaration::class)->latest('declared_at');
    }
}
