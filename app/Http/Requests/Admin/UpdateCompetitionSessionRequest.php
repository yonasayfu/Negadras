<?php

namespace App\Http\Requests\Admin;

use App\CompetitionSessionStatus;
use App\CompetitionSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('competition-sessions.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'season_id' => ['required', 'exists:seasons,id'],
            'stage_id' => ['required', 'exists:stages,id'],
            'panel_id' => ['nullable', 'exists:panels,id'],
            'name' => ['required', 'string', 'max:255'],
            'session_type' => ['required', Rule::enum(CompetitionSessionType::class)],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CompetitionSessionStatus::class)],
            'etv_video_url_optional' => ['nullable', 'url', 'max:500'],
            'media_ids' => ['array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
