<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('live-operations.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::in(['request', 'approve', 'end'])],
            'source_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
