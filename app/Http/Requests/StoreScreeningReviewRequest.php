<?php

namespace App\Http\Requests;

use App\Models\ReviewerAssignment;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'eligibility_checklist' => ['nullable', 'array'],
            'eligibility_checklist.identity_verified' => ['nullable', 'boolean'],
            'eligibility_checklist.problem_is_clear' => ['nullable', 'boolean'],
            'eligibility_checklist.solution_is_defined' => ['nullable', 'boolean'],
            'eligibility_checklist.files_are_complete' => ['nullable', 'boolean'],
            'recommendation' => [$submitting ? 'required' : 'nullable', Rule::enum(ScreeningRecommendation::class)],
            'score_optional' => ['nullable', 'integer', 'between:1,100'],
            'notes' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('intent') !== 'submit') {
                return;
            }

            $checklist = collect($this->input('eligibility_checklist', []));

            if ($checklist->isEmpty() || $checklist->contains(fn (mixed $value): bool => $value !== true)) {
                $validator->errors()->add(
                    'eligibility_checklist',
                    'All eligibility checklist items must be confirmed before submitting the review.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => $this->string('intent')->toString() ?: 'draft',
            'eligibility_checklist' => [
                'identity_verified' => $this->boolean('eligibility_checklist.identity_verified'),
                'problem_is_clear' => $this->boolean('eligibility_checklist.problem_is_clear'),
                'solution_is_defined' => $this->boolean('eligibility_checklist.solution_is_defined'),
                'files_are_complete' => $this->boolean('eligibility_checklist.files_are_complete'),
            ],
        ]);
    }
}
