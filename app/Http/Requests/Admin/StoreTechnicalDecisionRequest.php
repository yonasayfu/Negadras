<?php

namespace App\Http\Requests\Admin;

use App\Models\Submission;
use App\ReviewDecisionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicalDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission && ($this->user()?->can('update', $submission) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision_type' => ['required', Rule::enum(ReviewDecisionType::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
