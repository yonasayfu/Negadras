<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateRankingSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'integer', 'exists:stages,id'],
            'competition_session_id' => ['nullable', 'integer', 'exists:competition_sessions,id'],
            'finalize' => ['nullable', 'boolean'],
            'scope' => ['nullable', Rule::in(['stage', 'session'])],
        ];
    }
}
