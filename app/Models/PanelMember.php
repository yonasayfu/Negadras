<?php

namespace App\Models;

use App\PanelRole;
use Database\Factories\PanelMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelMember extends Model
{
    /** @use HasFactory<PanelMemberFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'panel_id',
        'judge_id',
        'role_in_panel',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_in_panel' => PanelRole::class,
        ];
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
