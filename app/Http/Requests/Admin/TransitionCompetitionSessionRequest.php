<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionCompetitionSessionRequest extends FormRequest
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
            'intent' => ['required', Rule::in([
                'start',
                'pause',
                'resume',
                'complete',
                'activate_presenter',
                'advance_presenter',
                'reveal_scores',
                'hide_scores',
            ])],
            'session_presenter_id' => ['nullable', 'integer', 'exists:session_presenters,id'],
        ];
    }
}
