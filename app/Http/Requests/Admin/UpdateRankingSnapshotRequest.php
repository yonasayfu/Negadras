<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRankingSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rank_position' => ['required', 'integer', 'min:1'],
            'override_reason_optional' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
