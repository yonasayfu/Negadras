<?php

namespace App\Http\Requests;

use App\Models\ReviewerAssignment;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreeningReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('reviewerAssignment');

        return $assignment instanceof ReviewerAssignment && ($this->user()?->can('update', $assignment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $submitting = $this->string('intent')->toString() === 'submit';

        return [
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'eligibility_status' => [$submitting ? 'required' : 'nullable', Rule::enum(ScreeningEligibilityStatus::class)],
            'recommendation' => [$submitting ? 'required' : 'nullable', Rule::enum(ScreeningRecommendation::class)],
            'score_optional' => ['nullable', 'integer', 'between:1,100'],
            'notes' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => $this->string('intent')->toString() ?: 'draft',
        ]);
    }
}
