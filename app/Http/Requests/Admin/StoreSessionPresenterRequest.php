<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionPresenterRequest extends FormRequest
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
            'submission_id' => ['required', 'exists:submissions,id'],
        ];
    }
}
