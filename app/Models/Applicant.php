<?php

namespace App\Models;

use App\ApplicantType;
use Database\Factories\ApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    /** @use HasFactory<ApplicantFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'applicant_type',
        'full_name',
        'email',
        'phone',
        'bio',
        'national_id_or_registration_ref',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applicant_type' => ApplicantType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class)->orderBy('platform');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class)->orderByDesc('is_primary_contact');
    }
}
