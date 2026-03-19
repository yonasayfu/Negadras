<?php

namespace App\Models;

use App\SeasonStatus;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'year',
        'slug',
        'status',
        'registration_open_at',
        'registration_close_at',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SeasonStatus::class,
            'registration_open_at' => 'datetime',
            'registration_close_at' => 'datetime',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('order_index');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->latest();
    }
}
