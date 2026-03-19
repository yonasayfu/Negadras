<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScoreLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('panel-scoring.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::in(['lock', 'unlock'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
