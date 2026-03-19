<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'display_name',
        'registration_number',
        'industry_id',
        'website',
        'description',
        'contact_email',
        'contact_phone',
        'logo_path',
        'address',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->orderByDesc('is_primary_contact')->orderBy('full_name');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(TeamMember::class)->where('is_primary_contact', true);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'attachable')->latest();
    }

    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'attachable')
            ->where('collection', 'organization-logo')
            ->latestOfMany();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->latest();
    }

    public function isManagedBy(User $user): bool
    {
        $applicantId = $user->applicant?->id;

        if ($applicantId === null) {
            return false;
        }

        return $this->teamMembers()
            ->where('applicant_id', $applicantId)
            ->where('is_primary_contact', true)
            ->exists();
    }
}
