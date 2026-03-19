<?php

namespace App\Http\Requests\Admin;

use App\AwardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAwardRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'integer', 'exists:submissions,id'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'ranking_snapshot_id' => ['nullable', 'integer', 'exists:ranking_snapshots,id'],
            'award_type' => ['required', Rule::enum(AwardType::class)],
            'rank_position' => ['nullable', 'integer', 'min:1'],
            'prize_value_optional' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
