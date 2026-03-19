<?php

namespace App\Http\Requests\Admin;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicalReviewerAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReviewerAssignment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reviewer_id' => [
                'required',
                'integer',
                Rule::exists(Reviewer::class, 'id'),
            ],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_at.after_or_equal' => 'Technical review due date cannot be in the past.',
        ];
    }
}
