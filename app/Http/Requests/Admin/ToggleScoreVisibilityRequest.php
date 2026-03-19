<?php

namespace App\Http\Requests\Admin;

use App\ScoreVisibilityAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleScoreVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('panel-scoring.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(ScoreVisibilityAction::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
