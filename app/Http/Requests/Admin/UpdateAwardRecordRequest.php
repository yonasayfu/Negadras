<?php

namespace App\Http\Requests\Admin;

use App\AwardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAwardRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'award_type' => ['required', Rule::enum(AwardType::class)],
            'rank_position' => ['nullable', 'integer', 'min:1'],
            'prize_value_optional' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
