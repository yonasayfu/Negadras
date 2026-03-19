<?php

namespace App\Http\Requests;

use App\Models\ReviewerAssignment;
use App\TechnicalReviewRecommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('reviewerAssignment');

        return $assignment instanceof ReviewerAssignment && ($this->user()?->can('update', $assignment) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $submitting = $this->string('intent')->toString() === 'submit';

        return [
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'innovation_score_optional' => ['nullable', 'integer', 'between:1,100'],
            'feasibility_score_optional' => ['nullable', 'integer', 'between:1,100'],
            'execution_score_optional' => ['nullable', 'integer', 'between:1,100'],
            'market_score_optional' => ['nullable', 'integer', 'between:1,100'],
            'strengths' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'weaknesses' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'risk_note' => ['nullable', 'string', 'max:10000'],
            'recommendation' => [$submitting ? 'required' : 'nullable', Rule::enum(TechnicalReviewRecommendation::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => $this->string('intent')->toString() ?: 'draft',
        ]);
    }
}
