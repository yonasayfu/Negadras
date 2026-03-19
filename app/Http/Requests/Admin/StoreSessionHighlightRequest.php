<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionHighlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competition_session_id' => ['required', 'integer', 'exists:competition_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'quote_optional' => ['nullable', 'string'],
            'quote_source_optional' => ['nullable', 'string', 'max:255'],
            'display_order' => ['required', 'integer', 'min:1'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
